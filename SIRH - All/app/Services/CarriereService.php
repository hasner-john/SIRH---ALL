<?php

declare(strict_types=1);

namespace App\Services;

use App\Libraries\Audit;
use App\Libraries\Transitions;
use Config\Database;

/** Mouvements de carrière : création, application (salaire / nouveau contrat / affectation), mouvements planifiés. */
class CarriereService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Règle de non-chevauchement : une nouvelle affectation à $dateDebut est refusée si elle commence
     * avant ou le jour du début de l'affectation en cours, ou avant la fin d'une affectation existante.
     * Retourne un message d'erreur, ou null si possible.
     */
    public static function verifierChevauchement(int $idEmploye, string $dateDebut): ?string
    {
        $db = Database::connect();
        $x = $db->query(
            'SELECT date_debut, date_fin FROM affectation WHERE id_employe = ? AND (date_fin IS NULL OR date_fin >= ?) AND date_debut >= ?
             ORDER BY date_debut LIMIT 1',
            [$idEmploye, $dateDebut, $dateDebut]
        )->getRowArray();
        if ($x) {
            return 'Chevauchement : une affectation existe déjà à partir du ' . $x['date_debut'] . ' (nouvelle date : ' . $dateDebut . ').';
        }
        $c = $db->query(
            'SELECT date_debut FROM affectation WHERE id_employe = ? AND date_fin IS NULL AND date_debut >= ?', [$idEmploye, $dateDebut]
        )->getRowArray();
        return $c ? 'Chevauchement avec l’affectation en cours (début ' . $c['date_debut'] . ').' : null;
    }

    /** Crée un mouvement (anciennes valeurs lues en base) ; l'applique tout de suite si la date d'effet est atteinte. */
    public function creer(array $d, int $userId): array
    {
        $emp = $this->db->table('employe e')->select('e.*, c.salaire, c.type_contrat')->join('contrat c', 'c.id_contrat = e.id_contrat')
            ->where('e.id_employe', (int) $d['id_employe'])->get()->getRowArray();
        if (! $emp || $emp['statut'] !== 'ACTIF') {
            throw new \RuntimeException('Employé actif introuvable.');
        }
        $type = (string) $d['type_mouvement'];
        if (! in_array($type, ['PROMOTION', 'MUTATION', 'CHANGEMENT_STATUT', 'REVISION_SALAIRE', 'AFFECTATION', 'CHANGEMENT_POSTE'], true)) {
            throw new \RuntimeException('Type de mouvement invalide.');
        }
        if (trim((string) ($d['motif'] ?? '')) === '' || empty($d['date_effet'])) {
            throw new \RuntimeException('Date d’effet et motif obligatoires.');
        }
        $nouveauSalaire = ($d['nouveau_salaire'] ?? '') === '' ? null : (float) $d['nouveau_salaire'];
        if ($type === 'REVISION_SALAIRE' && $nouveauSalaire === null) {
            throw new \RuntimeException('Le nouveau salaire est requis.');
        }
        if ($type === 'CHANGEMENT_POSTE' && empty($d['nouveau_id_poste'])) {
            throw new \RuntimeException('Le nouveau poste est requis.');
        }
        if ($type === 'MUTATION' && empty($d['id_departement'])) {
            throw new \RuntimeException('Le nouveau département est requis.');
        }
        if ($type === 'CHANGEMENT_STATUT' && empty($d['nouveau_type_contrat'])) {
            throw new \RuntimeException('Le nouveau type de contrat est requis.');
        }
        $aff = $this->db->table('affectation')->where(['id_employe' => $emp['id_employe'], 'date_fin' => null])->get()->getRowArray();
        $poste = $emp['id_poste'] ? $this->db->table('poste')->where('id_poste', $emp['id_poste'])->get()->getRowArray() : null;

        $this->db->table('mouvement_carriere')->insert([
            'id_employe' => (int) $emp['id_employe'], 'type_mouvement' => $type,
            'ancien_poste' => $aff['fonction'] ?? ($poste['libelle'] ?? null), 'nouveau_poste' => trim((string) ($d['nouveau_poste'] ?? '')) ?: null,
            'ancien_id_poste' => $emp['id_poste'], 'nouveau_id_poste' => (int) ($d['nouveau_id_poste'] ?? 0) ?: null,
            'ancien_salaire' => $emp['salaire'], 'nouveau_salaire' => $nouveauSalaire,
            'id_departement' => (int) ($d['id_departement'] ?? 0) ?: null, 'nouveau_type_contrat' => ($d['nouveau_type_contrat'] ?? '') ?: null,
            'date_effet' => $d['date_effet'], 'date_fin' => ($d['date_fin'] ?? '') ?: null, 'motif' => trim($d['motif']),
            'statut' => 'PLANIFIE', 'id_auteur' => $userId,
        ]);
        $id = (int) $this->db->insertID();
        Audit::log('CREATE', 'mouvement_carriere', $id, null, ['type' => $type, 'date_effet' => $d['date_effet']], $d['motif']);
        if ($d['date_effet'] <= date('Y-m-d')) {
            try {
                $this->appliquer($id, $userId);
            } catch (\Throwable $t) {
                // un mouvement refusé à la saisie ne doit pas rester en attente
                $this->db->table('mouvement_carriere')->where('id_mouvement', $id)->delete();
                Audit::log('REFUS', 'mouvement_carriere', $id, null, null, $t->getMessage());
                throw $t;
            }
            return [$id, 'APPLIQUE'];
        }
        return [$id, 'PLANIFIE'];
    }

    /** Applique un mouvement PLANIFIE (transaction unique). Lève RuntimeException si impossible (rollback complet). */
    public function appliquer(int $idM, int $userId): void
    {
        $m = $this->db->table('mouvement_carriere')->where('id_mouvement', $idM)->get()->getRowArray();
        if (! $m || $m['statut'] !== 'PLANIFIE') {
            throw new \RuntimeException('Mouvement introuvable ou déjà traité.');
        }
        $db = $this->db;
        $db->transBegin();
        try {
            $e = $db->table('employe')->where('id_employe', $m['id_employe'])->get()->getRowArray();
            $contrat = $db->table('contrat')->where('id_contrat', $e['id_contrat'])->get()->getRowArray();
            if ($e['statut'] !== 'ACTIF') {
                throw new \RuntimeException('L’employé n’est plus actif.');
            }
            $dateEffet = $m['date_effet'];
            $type = $m['type_mouvement'];
            $nouveauPoste = $m['nouveau_id_poste'] && (int) $m['nouveau_id_poste'] !== (int) $e['id_poste'] ? (int) $m['nouveau_id_poste'] : null;
            $nouveauContrat = in_array($type, ['CHANGEMENT_POSTE', 'CHANGEMENT_STATUT'], true) || $nouveauPoste !== null;
            $nouveauSalaire = $m['nouveau_salaire'] !== null ? (float) $m['nouveau_salaire'] : null;
            $idAncien = (int) $contrat['id_contrat'];
            $idNouveau = $idAncien;

            if ($nouveauContrat) {
                // Cas B : nouveau contrat obligatoire, ancien REMPLACE
                if ($dateEffet <= $contrat['date_debut']) {
                    throw new \RuntimeException('La date d’effet doit être postérieure au début du contrat actuel (' . $contrat['date_debut'] . ').');
                }
                $typeC = $m['nouveau_type_contrat'] ?: $contrat['type_contrat'];
                $fin = $typeC === 'CDI' ? null : ($m['date_fin'] ?: ($contrat['date_fin'] && $contrat['date_fin'] >= $dateEffet ? $contrat['date_fin'] : null));
                if ($typeC !== 'CDI' && ! $fin) {
                    throw new \RuntimeException('Une date de fin est obligatoire pour un contrat ' . $typeC . '.');
                }
                $origine = ['CHANGEMENT_POSTE' => 'CHANGEMENT_POSTE', 'PROMOTION' => 'PROMOTION', 'CHANGEMENT_STATUT' => 'CHANGEMENT_STATUT', 'MUTATION' => 'MUTATION'][$type] ?? 'CHANGEMENT_POSTE';
                $db->table('contrat')->insert([
                    'id_candidature' => null, 'id_poste' => $nouveauPoste ?? $e['id_poste'], 'origine' => $origine, 'id_contrat_precedent' => $idAncien,
                    'reference' => $this->referenceSuivante(), 'type_contrat' => $typeC, 'date_debut' => $dateEffet, 'date_fin' => $fin,
                    'salaire' => $nouveauSalaire ?? $contrat['salaire'], 'periodicite_salaire' => $contrat['periodicite_salaire'],
                    'lieu_travail' => $contrat['lieu_travail'], 'missions' => $contrat['missions'], 'horaires' => $contrat['horaires'],
                    'conges_absences' => $contrat['conges_absences'], 'statut' => 'SIGNE', 'date_signature' => date('Y-m-d H:i:s'),
                ]);
                $idNouveau = (int) $db->insertID();
                [$ok, $msg] = Transitions::appliquer('contrat', $idAncien, 'REMPLACE', 'Mouvement #' . $idM, ['date_fin' => date('Y-m-d', strtotime($dateEffet . ' -1 day'))]);
                if (! $ok) {
                    throw new \RuntimeException($msg);
                }
                $db->table('historique_contrat')->insert(['id_contrat' => $idAncien, 'ancien_statut' => 'SIGNE', 'nouveau_statut' => 'REMPLACE', 'id_utilisateur' => $userId, 'commentaire' => 'Remplacé par le contrat #' . $idNouveau]);
                $db->table('historique_contrat')->insert(['id_contrat' => $idNouveau, 'ancien_statut' => null, 'nouveau_statut' => 'SIGNE', 'id_utilisateur' => $userId, 'commentaire' => $type . ' (mouvement #' . $idM . ')']);
                $db->table('employe')->where('id_employe', $e['id_employe'])->update(['id_contrat' => $idNouveau, 'id_poste' => $nouveauPoste ?? $e['id_poste']]);
                (new ContratPdfService())->generer($idNouveau);
            } elseif ($nouveauSalaire !== null && abs($nouveauSalaire - (float) $contrat['salaire']) > 0.001) {
                // Cas A : salaire seul
                $db->table('contrat')->where('id_contrat', $idAncien)->update(['salaire' => $nouveauSalaire]);
            }
            if ($nouveauSalaire !== null && abs($nouveauSalaire - (float) $contrat['salaire']) > 0.001) {
                $db->table('historique_salaire')->insert([
                    'id_employe' => $e['id_employe'], 'ancien_salaire' => $contrat['salaire'], 'nouveau_salaire' => $nouveauSalaire,
                    'periodicite_salaire' => $contrat['periodicite_salaire'], 'motif' => $type . ' : ' . $m['motif'], 'id_utilisateur' => $userId, 'date_effet' => $dateEffet,
                ]);
            }

            // Affectation (fonction ou département qui change), sans chevauchement
            $aff = $db->table('affectation')->where(['id_employe' => $e['id_employe'], 'date_fin' => null])->get()->getRowArray();
            $nouvelleFonction = $m['nouveau_poste'] ?: ($aff['fonction'] ?? null);
            $nouveauDep = $m['id_departement'] ?: ($aff['id_departement'] ?? null);
            $change = $aff && ($nouvelleFonction !== $aff['fonction'] || (int) $nouveauDep !== (int) $aff['id_departement']);
            if (! $m['nouveau_poste'] && $nouveauPoste) {
                $nouvelleFonction = (string) $db->table('poste')->where('id_poste', $nouveauPoste)->get()->getRow('libelle');
                $change = true;
            }
            if ($change || ! $aff && $nouveauDep) {
                if ($aff && $dateEffet <= $aff['date_debut']) {
                    throw new \RuntimeException('Chevauchement : la date d’effet doit suivre le début de l’affectation en cours (' . $aff['date_debut'] . ').');
                }
                if (($err = self::verifierChevauchement((int) $e['id_employe'], $dateEffet)) !== null) {
                    throw new \RuntimeException($err);
                }
                if ($aff) {
                    $db->table('affectation')->where('id_affectation', $aff['id_affectation'])->update(['date_fin' => date('Y-m-d', strtotime($dateEffet . ' -1 day'))]);
                }
                $db->table('affectation')->insert([
                    'id_employe' => $e['id_employe'], 'id_departement' => $nouveauDep, 'date_debut' => $dateEffet, 'fonction' => $nouvelleFonction,
                ]);
            }

            $poste = $db->table('poste')->where('id_poste', $e['id_poste'])->get()->getRowArray();
            $db->table('mouvement_carriere')->where('id_mouvement', $idM)->update([
                'ancien_poste' => $aff['fonction'] ?? ($poste['libelle'] ?? $m['ancien_poste']), 'ancien_id_poste' => $e['id_poste'], 'ancien_salaire' => $contrat['salaire'],
                'id_ancien_contrat' => $idAncien, 'id_nouveau_contrat' => $idNouveau,
            ]);
            [$ok, $msg] = Transitions::appliquer('mouvement_carriere', $idM, 'APPLIQUE', $m['motif']);
            if (! $ok) {
                throw new \RuntimeException($msg);
            }
            NotificationService::versEmploye((int) $e['id_employe'], 'Mouvement de carrière', $type . ' à compter du ' . $dateEffet . ' : ' . $m['motif'], '/employe');
            if (! $db->transStatus()) {
                throw new \RuntimeException('Erreur base de données.');
            }
            $db->transCommit();
        } catch (\Throwable $t) {
            $db->transRollback();
            throw $t;
        }
    }

    /** Applique tous les mouvements PLANIFIE dont la date d'effet est atteinte. Retourne [appliqués, erreurs[]]. */
    public function appliquerEchus(int $userId = 0): array
    {
        $ids = $this->db->table('mouvement_carriere')->select('id_mouvement')->where('statut', 'PLANIFIE')->where('date_effet <=', date('Y-m-d'))->orderBy('date_effet')->get()->getResultArray();
        $n = 0;
        $erreurs = [];
        foreach ($ids as $r) {
            try {
                $this->appliquer((int) $r['id_mouvement'], $userId);
                $n++;
            } catch (\Throwable $t) {
                $erreurs[] = '#' . $r['id_mouvement'] . ' : ' . $t->getMessage();
            }
        }
        return [$n, $erreurs];
    }

    private function referenceSuivante(): string
    {
        $an = date('Y');
        $max = 0;
        foreach ($this->db->table('contrat')->select('reference')->like('reference', "CTR-$an-", 'after')->get()->getResultArray() as $r) {
            $max = max($max, (int) substr($r['reference'], strlen("CTR-$an-")));
        }
        return sprintf('CTR-%s-%03d', $an, $max + 1);
    }
}
