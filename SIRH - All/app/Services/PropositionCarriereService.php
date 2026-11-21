<?php

declare(strict_types=1);

namespace App\Services;

use App\Libraries\ParametreRh;
use Config\Database;

/** Propositions de carrière R1 à R4 (réservées à la RH). Tous les seuils viennent de parametre_rh. */
class PropositionCarriereService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /** Génère les propositions manquantes. Retourne le nombre créé. */
    public function generer(): int
    {
        $crees = 0;
        $emps = $this->db->table('employe e')->select('e.*, c.salaire')->join('contrat c', 'c.id_contrat = e.id_contrat')->where('e.statut', 'ACTIF')->get()->getResultArray();
        foreach ($emps as $e) {
            if ($this->bloque($e)) {
                continue;
            }
            $eval = $this->db->table('evaluation_performance')->where('id_employe', $e['id_employe'])->where('statut', 'VALIDE')->where('type_evaluation !=', 'POST_FORMATION')
                ->where('note_globale IS NOT NULL', null, false)->orderBy('date_evaluation', 'DESC')->orderBy('id_evaluation_performance', 'DESC')->get(1)->getRowArray();
            $note = $eval ? (float) $eval['note_globale'] : null;
            $ecartMoy = $eval ? $this->db->table('ecart_competence')->selectAvg('ecart', 'm')->where('id_evaluation_performance', $eval['id_evaluation_performance'])->get()->getRow('m') : null;
            $ecartMoy = $ecartMoy === null ? null : (float) $ecartMoy;
            $ancMois = (int) (new \DateTime($e['date_embauche']))->diff(new \DateTime())->format('%y') * 12 + (int) (new \DateTime($e['date_embauche']))->diff(new \DateTime())->format('%m');
            $ancAns = intdiv($ancMois, 12);
            $dernierSal = $this->db->table('historique_salaire')->selectMax('date_effet', 'd')->where('id_employe', $e['id_employe'])->get()->getRow('d') ?: $e['date_embauche'];
            $moisSal = (int) (new \DateTime($dernierSal))->diff(new \DateTime())->format('%y') * 12 + (int) (new \DateTime($dernierSal))->diff(new \DateTime())->format('%m');

            // R1 : promotion à l'ancienneté
            $a = ParametreRh::get('CARR_PROMO_ANCIENNETE_ANS');
            $n = ParametreRh::get('CARR_PROMO_NOTE_MIN');
            if ($a !== null && $n !== null && $note !== null && $ancAns >= $a && $note >= $n) {
                $crees += $this->creer($e, 'PROMOTION', 'R1_ANCIENNETE', sprintf('Ancienneté %d ans (seuil %s), note %s/20 (seuil %s)', $ancAns, $a, $note, $n), null, null, 'Fonction supérieure');
            }
            // R2 : promotion à la performance
            $pn = ParametreRh::get('CARR_PROMO_PERF_NOTE_MIN');
            $pa = ParametreRh::get('CARR_PROMO_PERF_ANCIENNETE_MOIS');
            if ($pn !== null && $pa !== null && $note !== null && $note >= $pn && $ancMois >= $pa && $ecartMoy !== null && $ecartMoy <= 0) {
                $crees += $this->creer($e, 'PROMOTION', 'R2_PERFORMANCE', sprintf('Note %s/20 (seuil %s), ancienneté %d mois (seuil %s), écart moyen de compétences %.2f (≤ 0)', $note, $pn, $ancMois, $pa, $ecartMoy), null, null, 'Fonction supérieure');
            }
            // R3 : augmentation
            $am = ParametreRh::get('CARR_AUGM_ANCIENNETE_MOIS');
            $an = ParametreRh::get('CARR_AUGM_NOTE_MIN');
            $ap = ParametreRh::get('CARR_AUGM_POURCENT');
            if ($am !== null && $an !== null && $ap !== null && $note !== null && $moisSal >= $am && $note >= $an) {
                $nouveau = round((float) $e['salaire'] * (1 + $ap / 100), 2);
                $crees += $this->creer($e, 'AUGMENTATION', 'R3_AUGMENTATION', sprintf('Dernier changement de salaire il y a %d mois (seuil %s), note %s/20 (seuil %s) : +%s%%', $moisSal, $am, $note, $an, $ap), $nouveau, null, null);
            }
            // R4 : surperformance → changement de poste
            $se = ParametreRh::get('CARR_SURPERF_ECART');
            if ($se !== null && $ecartMoy !== null && $note !== null && $ecartMoy <= $se) {
                $p = $this->meilleurPoste($e);
                if ($p) {
                    $crees += $this->creer($e, 'CHANGEMENT_POSTE', 'R4_SURPERFORMANCE', sprintf('Écart moyen de compétences %.2f (seuil %s) : profil adapté au poste « %s »', $ecartMoy, $se, $p['libelle']), null, (int) $p['id_poste'], $p['libelle']);
                }
            }
        }
        if ($crees > 0) {
            NotificationService::versRh('Propositions de carrière', "$crees nouvelle(s) proposition(s) de carrière.", '/rh/carriere/propositions');
        }
        return $crees;
    }

    /** Garde-fous : aucune proposition si l'une de ces conditions est vraie. */
    private function bloque(array $e): bool
    {
        $db = $this->db;
        if ($db->table('rupture_contrat')->where('id_employe', $e['id_employe'])->where('statut !=', 'ANNULEE')->countAllResults() > 0) {
            return true;
        }
        $mS = (int) ParametreRh::get('CARR_BLOQUE_SANCTION_MOIS', 12);
        $sanc = $mS <= 0 ? 0 : $db->query(
            "SELECT COUNT(*) n FROM sanction s JOIN incident i ON i.id_incident = s.id_incident
             WHERE i.id_employe = ? AND s.type_sanction IN ('BLAME','MISE_A_PIED','LICENCIEMENT') AND s.statut != 'ANNULEE' AND s.date_decision >= ?",
            [$e['id_employe'], date('Y-m-d', strtotime("-$mS months"))]
        )->getRow('n');
        if ((int) $sanc > 0) {
            return true;
        }
        $mM = (int) ParametreRh::get('CARR_DELAI_MIN_MOUVEMENT_MOIS', 12);
        return $mM > 0 && $db->table('mouvement_carriere')->where('id_employe', $e['id_employe'])->where('statut', 'APPLIQUE')
            ->where('date_effet >=', date('Y-m-d', strtotime("-$mM months")))->countAllResults() > 0;
    }

    private function creer(array $e, string $type, string $regle, string $justif, ?float $salaire, ?int $idPoste, ?string $fonction): int
    {
        $db = $this->db;
        if ($db->table('proposition_carriere')->where(['id_employe' => $e['id_employe'], 'type_proposition' => $type, 'statut' => 'EN_ATTENTE'])->countAllResults() > 0) {
            return 0;
        }
        $dR = (int) ParametreRh::get('CARR_DELAI_REPROPOSITION_MOIS', 6);
        if ($db->table('proposition_carriere')->where(['id_employe' => $e['id_employe'], 'type_proposition' => $type, 'code_regle' => $regle, 'statut' => 'REFUSEE'])
            ->where('date_decision >=', date('Y-m-d H:i:s', strtotime("-$dR months")))->countAllResults() > 0) {
            return 0;
        }
        $db->table('proposition_carriere')->insert([
            'id_employe' => $e['id_employe'], 'type_proposition' => $type, 'code_regle' => $regle, 'justification' => $justif,
            'salaire_suggere' => $salaire, 'id_poste_suggere' => $idPoste, 'fonction_suggeree' => $fonction,
        ]);
        return 1;
    }

    /** Poste dont toutes les compétences sont couvertes (acquis ≥ attendu), plus forte somme de niveaux attendus, ≠ poste actuel. */
    private function meilleurPoste(array $e): ?array
    {
        $acquis = [];
        foreach ($this->db->query(
            'SELECT ec.id_competence, ec.niveau_acquis FROM employe_competence ec WHERE ec.id_employe = ?
             ORDER BY ec.date_evaluation ASC, ec.id_employe_competence ASC', [$e['id_employe']]
        )->getResultArray() as $r) {
            $acquis[(int) $r['id_competence']] = (int) $r['niveau_acquis'];   // le dernier écrase
        }
        $best = null;
        $bestSomme = -1;
        foreach ($this->db->table('poste')->where('statut', 'ACTIF')->where('id_poste !=', (int) $e['id_poste'])->get()->getResultArray() as $p) {
            $comps = $this->db->table('poste_competence')->where('id_poste', $p['id_poste'])->get()->getResultArray();
            if (! $comps) {
                continue;
            }
            $somme = 0;
            foreach ($comps as $c) {
                if (($acquis[(int) $c['id_competence']] ?? 0) < (int) $c['niveau_attendu']) {
                    continue 2;
                }
                $somme += (int) $c['niveau_attendu'];
            }
            if ($somme > $bestSomme) {
                $bestSomme = $somme;
                $best = $p;
            }
        }
        return $best;
    }
}
