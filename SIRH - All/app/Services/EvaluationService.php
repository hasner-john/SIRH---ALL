<?php

declare(strict_types=1);

namespace App\Services;

use App\Libraries\Audit;
use App\Libraries\ParametreRh;
use App\Libraries\Transitions;
use Config\Database;

/** Évaluations de performance, écarts de compétences, propositions de formation, apport des formations. */
class EvaluationService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Crée une évaluation BROUILLON + les niveaux acquis (employe_competence lié à l'évaluation).
     * $niveaux : [id_competence => niveau 1..5]. $objectifs : [id_objectif => ['statut' =>, 'resultat' =>]].
     */
    public function enregistrer(array $d, array $niveaux, array $objectifs, int $userId): int
    {
        $this->db->transBegin();
        try {
            $this->db->table('evaluation_performance')->insert([
                'id_employe' => (int) $d['id_employe'], 'id_evaluateur' => $userId, 'periode' => trim((string) $d['periode']),
                'type_evaluation' => $d['type_evaluation'] ?? 'PERIODIQUE', 'note_globale' => $d['note_globale'] === '' ? null : (float) $d['note_globale'],
                'commentaire' => $d['commentaire'] ?? null, 'statut' => 'BROUILLON', 'date_evaluation' => date('Y-m-d'),
                'id_formation' => $d['id_formation'] ?? null,
            ]);
            $id = (int) $this->db->insertID();
            $this->enregistrerNiveaux($id, (int) $d['id_employe'], $niveaux, $userId);
            foreach ($objectifs as $idObj => $o) {
                if (! empty($o['statut'])) {
                    $this->db->table('objectif')->where(['id_objectif' => (int) $idObj, 'id_employe' => (int) $d['id_employe']])
                        ->update(['statut' => $o['statut'], 'resultat' => $o['resultat'] ?? null]);
                }
            }
            Audit::log('CREATE', 'evaluation_performance', $id, null, ['periode' => $d['periode'], 'niveaux' => $niveaux]);
            if (! $this->db->transStatus()) {
                throw new \RuntimeException('Erreur base de données.');
            }
            $this->db->transCommit();
            return $id;
        } catch (\Throwable $t) {
            $this->db->transRollback();
            throw $t;
        }
    }

    private function enregistrerNiveaux(int $idEval, int $idEmploye, array $niveaux, int $userId): void
    {
        foreach ($niveaux as $idComp => $niv) {
            $niv = (int) $niv;
            if ($niv < 1 || $niv > 5) {
                continue;
            }
            $this->db->query(
                'INSERT INTO employe_competence (id_employe, id_competence, niveau_acquis, date_evaluation, id_evaluateur, id_evaluation_performance)
                 VALUES (?,?,?,?,?,?)
                 ON CONFLICT(id_employe, id_competence, date_evaluation)
                 DO UPDATE SET niveau_acquis = excluded.niveau_acquis, id_evaluation_performance = excluded.id_evaluation_performance, id_evaluateur = excluded.id_evaluateur',
                [$idEmploye, (int) $idComp, $niv, date('Y-m-d'), $userId, $idEval]
            );
        }
    }

    /** Valide l'évaluation : écarts figés, propositions de formation, notification RH, audit (transaction unique). */
    public function valider(int $idEval, int $userId): int
    {
        $ev = $this->db->table('evaluation_performance')->where('id_evaluation_performance', $idEval)->get()->getRowArray();
        if (! $ev) {
            throw new \RuntimeException('Évaluation introuvable.');
        }
        $this->db->transBegin();
        try {
            [$ok, $msg] = Transitions::appliquer('evaluation_performance', $idEval, 'VALIDE');
            if (! $ok) {
                throw new \RuntimeException($msg);
            }
            $nb = $this->figerEcarts($idEval, (int) $ev['id_employe']);
            if ($nb > 0) {
                NotificationService::versRh('Propositions de formation', "$nb proposition(s) de formation à traiter.", '/rh/formations/propositions');
            }
            if (! $this->db->transStatus()) {
                throw new \RuntimeException('Erreur base de données.');
            }
            $this->db->transCommit();
            return $nb;
        } catch (\Throwable $t) {
            $this->db->transRollback();
            throw $t;
        }
    }

    /** Calcule/fige les écarts d'une évaluation et crée les propositions. Retourne le nombre de propositions créées. */
    private function figerEcarts(int $idEval, int $idEmploye): int
    {
        $emp = $this->db->table('employe')->where('id_employe', $idEmploye)->get()->getRowArray();
        $lignes = $this->db->query(
            'SELECT ec.id_competence, ec.niveau_acquis, pc.niveau_attendu
             FROM employe_competence ec
             JOIN poste_competence pc ON pc.id_competence = ec.id_competence AND pc.id_poste = ?
             WHERE ec.id_evaluation_performance = ?',
            [$emp['id_poste'], $idEval]
        )->getResultArray();
        $seuil = (float) ParametreRh::get('SEUIL_ECART_FORMATION', 1);
        $crees = 0;
        foreach ($lignes as $l) {
            $ecart = (int) $l['niveau_attendu'] - (int) $l['niveau_acquis'];
            $this->db->query(
                'INSERT OR REPLACE INTO ecart_competence (id_evaluation_performance, id_employe, id_competence, niveau_attendu, niveau_acquis, ecart) VALUES (?,?,?,?,?,?)',
                [$idEval, $idEmploye, $l['id_competence'], $l['niveau_attendu'], $l['niveau_acquis'], $ecart]
            );
            $idEcart = (int) $this->db->table('ecart_competence')->where(['id_evaluation_performance' => $idEval, 'id_competence' => $l['id_competence']])->get()->getRow('id_ecart');
            if ($ecart > $seuil && $this->creerProposition($idEmploye, (int) $l['id_competence'], $idEcart, $ecart, $seuil)) {
                $crees++;
            }
        }
        Audit::log('VALIDATE', 'evaluation_performance', $idEval, 'BROUILLON', 'VALIDE', "$crees proposition(s)");
        return $crees;
    }

    private function creerProposition(int $idEmploye, int $idComp, int $idEcart, int $ecart, float $seuil): bool
    {
        if ($this->db->table('proposition_formation')->where(['id_employe' => $idEmploye, 'id_competence' => $idComp, 'statut' => 'EN_ATTENTE'])->countAllResults() > 0) {
            return false;
        }
        $this->db->table('proposition_formation')->insert([
            'id_employe' => $idEmploye, 'id_competence' => $idComp, 'id_ecart' => $idEcart, 'ecart' => $ecart, 'seuil_applique' => $seuil,
        ]);
        return true;
    }

    /** Après changement du seuil : crée les propositions manquantes, passe SANS_OBJET celles qui ne dépassent plus le seuil. */
    public function recalculer(): array
    {
        $seuil = (float) ParametreRh::get('SEUIL_ECART_FORMATION', 1);
        $derniers = $this->db->query(
            'SELECT e.* FROM ecart_competence e
             WHERE e.id_ecart = (SELECT e2.id_ecart FROM ecart_competence e2 JOIN evaluation_performance ev ON ev.id_evaluation_performance = e2.id_evaluation_performance
                                 WHERE e2.id_employe = e.id_employe AND e2.id_competence = e.id_competence
                                 ORDER BY ev.date_evaluation DESC, e2.id_ecart DESC LIMIT 1)'
        )->getResultArray();
        $cree = 0;
        $sansObjet = 0;
        $this->db->transBegin();
        foreach ($derniers as $e) {
            if ((int) $e['ecart'] > $seuil) {
                $cree += $this->creerProposition((int) $e['id_employe'], (int) $e['id_competence'], (int) $e['id_ecart'], (int) $e['ecart'], $seuil) ? 1 : 0;
            } else {
                $att = $this->db->table('proposition_formation')->where(['id_employe' => $e['id_employe'], 'id_competence' => $e['id_competence'], 'statut' => 'EN_ATTENTE'])->get()->getResultArray();
                foreach ($att as $pf) {
                    [$ok] = Transitions::appliquer('proposition_formation', (int) $pf['id_proposition_formation'], 'SANS_OBJET', 'Écart sous le seuil', ['date_decision' => date('Y-m-d H:i:s')]);
                    $sansObjet += $ok ? 1 : 0;
                }
            }
        }
        Audit::log('RECALCUL', 'proposition_formation', null, null, ['creees' => $cree, 'sans_objet' => $sansObjet, 'seuil' => $seuil]);
        $this->db->transCommit();
        return ['creees' => $cree, 'sans_objet' => $sansObjet];
    }

    /** Saisie du niveau après formation + évaluation POST_FORMATION validée avec écart figé. */
    public function niveauApres(int $idFormation, int $idEmploye, int $niveauApres, int $userId): void
    {
        $f = $this->db->table('formation')->where('id_formation', $idFormation)->get()->getRowArray();
        $part = $this->db->table('formation_participant')->where(['id_formation' => $idFormation, 'id_employe' => $idEmploye])->get()->getRowArray();
        if (! $f || ! $part || $f['statut'] !== 'TERMINEE' || $part['statut'] !== 'TERMINE' || ! $f['id_competence'] || $niveauApres < 1 || $niveauApres > 5) {
            throw new \RuntimeException('Formation terminée, participant terminé, compétence liée et niveau 1 à 5 requis.');
        }
        $emp = $this->db->table('employe')->where('id_employe', $idEmploye)->get()->getRowArray();
        $this->db->transBegin();
        try {
            $this->db->table('formation_participant')->where('id_participant', $part['id_participant'])->update(['niveau_apres' => $niveauApres]);
            $this->db->table('evaluation_performance')->insert([
                'id_employe' => $idEmploye, 'id_evaluateur' => $userId, 'periode' => 'POST-F' . $idFormation, 'type_evaluation' => 'POST_FORMATION',
                'id_formation' => $idFormation, 'commentaire' => 'Évaluation post-formation : ' . $f['titre'], 'statut' => 'VALIDE', 'date_evaluation' => date('Y-m-d'),
            ]);
            $idEval = (int) $this->db->insertID();
            $this->enregistrerNiveaux($idEval, $idEmploye, [$f['id_competence'] => $niveauApres], $userId);
            $attendu = $this->db->table('poste_competence')->where(['id_poste' => $emp['id_poste'], 'id_competence' => $f['id_competence']])->get()->getRow('niveau_attendu');
            if ($attendu !== null) {
                $ecart = (int) $attendu - $niveauApres;
                $this->db->table('ecart_competence')->insert([
                    'id_evaluation_performance' => $idEval, 'id_employe' => $idEmploye, 'id_competence' => $f['id_competence'],
                    'niveau_attendu' => $attendu, 'niveau_acquis' => $niveauApres, 'ecart' => $ecart,
                ]);
                $idEcart = (int) $this->db->insertID();
                $seuil = (float) ParametreRh::get('SEUIL_ECART_FORMATION', 1);
                if ($ecart > $seuil) {
                    $this->creerProposition($idEmploye, (int) $f['id_competence'], $idEcart, $ecart, $seuil);
                }
            }
            Audit::log('CREATE', 'evaluation_performance', $idEval, null, ['type' => 'POST_FORMATION', 'niveau_apres' => $niveauApres]);
            if (! $this->db->transStatus()) {
                throw new \RuntimeException('Erreur base de données.');
            }
            $this->db->transCommit();
        } catch (\Throwable $t) {
            $this->db->transRollback();
            throw $t;
        }
    }
}
