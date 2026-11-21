<?php

declare(strict_types=1);

namespace App\Services;

use App\Libraries\Audit;
use App\Libraries\ParametreRh;
use App\Libraries\Transitions;
use Config\Database;

class RuptureService
{
    public const CHECKLIST = ['Dernier bulletin de paie', 'Validation STC', 'Certificat de travail', 'Attestations sociales', 'Restitution matériel', 'Coupure des accès', 'Archivage dossier'];
    public const MOTIFS = ['DEMISSION', 'LICENCIEMENT_ECONOMIQUE', 'LICENCIEMENT_FAUTE', 'FIN_CDD', 'RETRAITE', 'RUPTURE_CONVENTIONNELLE', 'AUTRE'];

    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Crée une rupture (checklist, STC brouillon calculé, attestations vides). Appelée par la page Ruptures et par la discipline.
     * $d : id_employe, motif, date_notification, [date_sortie], [motif_detail], [duree_preavis_jours], [id_sanction], [statut].
     * Lève RuntimeException (message affichable) en cas de refus. Retourne l'id de la rupture.
     */
    public function creer(array $d, int $userId): int
    {
        $db = $this->db;
        $emp = $db->table('employe e')->select('e.*, c.date_fin, c.type_contrat')->join('contrat c', 'c.id_contrat = e.id_contrat')->where('e.id_employe', (int) $d['id_employe'])->get()->getRowArray();
        if (! $emp || ! in_array($emp['statut'], ['ACTIF', 'SUSPENDU'], true)) {
            throw new \RuntimeException('Employé actif introuvable.');
        }
        if ($db->table('rupture_contrat')->where('id_employe', $emp['id_employe'])->countAllResults() > 0) {
            throw new \RuntimeException('Cet employé a déjà une rupture de contrat enregistrée.');
        }
        $motif = (string) $d['motif'];
        if (! in_array($motif, self::MOTIFS, true)) {
            throw new \RuntimeException('Motif de rupture invalide.');
        }
        $notif = (string) $d['date_notification'];
        if (strtotime($notif) === false) {
            throw new \RuntimeException('Date de notification invalide.');
        }
        $preavis = isset($d['duree_preavis_jours']) && $d['duree_preavis_jours'] !== '' ? (int) $d['duree_preavis_jours'] : match ($motif) {
            'DEMISSION' => (int) ParametreRh::get('PREAVIS_DEMISSION_JOURS', 0),
            'LICENCIEMENT_ECONOMIQUE' => (int) ParametreRh::get('PREAVIS_LICENCIEMENT_JOURS', 0),
            default => 0,
        };
        if (! empty($d['date_sortie'])) {
            $sortie = (string) $d['date_sortie'];
        } elseif ($motif === 'FIN_CDD' && $emp['date_fin']) {
            $sortie = $emp['date_fin'];
        } else {
            $sortie = date('Y-m-d', strtotime("$notif +$preavis days"));
        }
        if ($sortie < $notif) {
            throw new \RuntimeException('La date de sortie précède la date de notification.');
        }

        $db->transBegin();
        try {
            $db->table('rupture_contrat')->insert([
                'id_employe' => $emp['id_employe'], 'motif' => $motif, 'date_notification' => $notif, 'date_sortie' => $sortie,
                'duree_preavis_jours' => $preavis, 'motif_detail' => trim((string) ($d['motif_detail'] ?? '')), 'statut' => $d['statut'] ?? 'OUVERT',
                'id_auteur' => $userId ?: null, 'id_sanction' => $d['id_sanction'] ?? null,
            ]);
            $id = (int) $db->insertID();
            foreach (self::CHECKLIST as $el) {
                $db->table('checklist_offboarding')->insert(['id_rupture' => $id, 'element' => $el, 'valeur_statut' => 'A_FAIRE', 'date_echeance' => $sortie]);
            }
            $an = date('Y');
            foreach (['CERTIFICAT_TRAVAIL' => 'CT', 'ATTESTATION_CNAPS' => 'CNAPS', 'ATTESTATION_OSTIE' => 'OSTIE'] as $type => $pre) {
                $db->table('attestation_travail')->insert(['id_rupture' => $id, 'type_attestation' => $type, 'reference' => sprintf('%s-%s-%03d', $pre, $an, $id)]);
            }
            (new StcService())->calculer($id);
            Audit::log('CREATE', 'rupture_contrat', $id, null, ['motif' => $motif, 'date_sortie' => $sortie], (string) ($d['motif_detail'] ?? ''));
            NotificationService::versRh('Nouvelle rupture de contrat', 'Rupture ' . $motif . ' enregistrée, sortie prévue le ' . $sortie . '.', '/rh/ruptures/' . $id);
            if (! $db->transStatus()) {
                throw new \RuntimeException('Erreur base de données lors de la création de la rupture.');
            }
            $db->transCommit();
            return $id;
        } catch (\Throwable $t) {
            $db->transRollback();
            throw $t;
        }
    }

    /** Changement de statut de la rupture (via Transitions) avec contrôles métier. Retourne [ok, message]. */
    public function statut(int $id, string $vers, int $userId): array
    {
        $r = $this->db->table('rupture_contrat')->where('id_rupture', $id)->get()->getRowArray();
        if (! $r) {
            return [false, 'Rupture introuvable.'];
        }
        $extra = [];
        if ($vers === 'VALIDEE') {
            $extra = ['id_validateur' => $userId, 'date_validation' => gmdate('Y-m-d H:i:s')];
        }
        if ($vers === 'CLOTURE') {
            $reste = $this->db->table('checklist_offboarding')->where('id_rupture', $id)->whereNotIn('valeur_statut', ['TERMINE', 'NON_APPLICABLE'])->countAllResults();
            $stc = $this->db->table('solde_tout_compte')->where('id_rupture', $id)->get()->getRowArray();
            if ($reste > 0 || ! $stc || $stc['statut'] !== 'PAIE') {
                return [false, 'Clôture impossible : checklist complète et STC payé requis.'];
            }
        }
        $this->db->transBegin();
        $res = Transitions::appliquer('rupture_contrat', $id, $vers, null, $extra);
        if (! $res[0]) {
            $this->db->transRollback();
            return $res;
        }
        if ($vers === 'VALIDEE') {
            $this->appliquerSorties();
        }
        if (! $this->db->transStatus()) {
            $this->db->transRollback();
            return [false, 'Erreur base de données.'];
        }
        $this->db->transCommit();
        return [true, ''];
    }

    /** Pour chaque rupture VALIDEE dont la date de sortie est atteinte : employé PARTI, contrat TERMINE, accès coupés. */
    public function appliquerSorties(): int
    {
        $db = $this->db;
        $rows = $db->table('rupture_contrat r')->select('r.*, e.statut AS statut_employe, e.id_contrat, e.id_personne')->join('employe e', 'e.id_employe = r.id_employe')
            ->where('r.statut', 'VALIDEE')->where('r.date_sortie <=', date('Y-m-d'))->where('e.statut !=', 'PARTI')->get()->getResultArray();
        $n = 0;
        foreach ($rows as $r) {
            [$ok] = Transitions::appliquer('employe', (int) $r['id_employe'], 'PARTI', 'Rupture #' . $r['id_rupture']);
            if (! $ok) {
                continue;
            }
            $db->table('historique_employe')->insert(['id_employe' => $r['id_employe'], 'ancien_statut' => $r['statut_employe'], 'nouveau_statut' => 'PARTI', 'id_utilisateur' => session()->get('id_utilisateur') ?: null, 'commentaire' => 'Rupture : ' . $r['motif']]);
            $c = $db->table('contrat')->where('id_contrat', $r['id_contrat'])->get()->getRowArray();
            if ($c && $c['statut'] === 'SIGNE') {
                Transitions::appliquer('contrat', (int) $c['id_contrat'], 'TERMINE', 'Rupture #' . $r['id_rupture'], $c['date_fin'] ? [] : ['date_fin' => $r['date_sortie']]);
            }
            $db->table('affectation')->where(['id_employe' => $r['id_employe'], 'date_fin' => null])->update(['date_fin' => $r['date_sortie']]);
            $idUser = $db->table('personne')->where('id_personne', $r['id_personne'])->get()->getRow('id_utilisateur');
            if ($idUser) {
                $db->table('utilisateur')->where('id_utilisateur', $idUser)->update(['actif' => 0]);
            }
            $db->table('checklist_offboarding')->where(['id_rupture' => $r['id_rupture'], 'element' => 'Coupure des accès'])
                ->update(['valeur_statut' => 'TERMINE', 'date_realisation' => gmdate('Y-m-d H:i:s')]);
            Audit::log('SORTIE', 'employe', (int) $r['id_employe'], 'ACTIF', 'PARTI', 'Accès coupés');
            $n++;
        }
        return $n;
    }
}
