<?php

declare(strict_types=1);

namespace App\Services;

use App\Libraries\Audit;
use App\Libraries\ParametreRh;
use App\Libraries\Transitions;
use Config\Database;

/** Procédure disciplinaire : incident → explications → réponse → (commission) → décision → sanction. */
class DisciplineService
{
    public const PIECE_MAX = 2 * 1024 * 1024;

    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function creerIncident(array $d, int $userId): int
    {
        if ((int) $d['id_employe'] <= 0 || trim((string) $d['objet']) === '' || trim((string) $d['description']) === '' || empty($d['date_incident'])) {
            throw new \RuntimeException('Employé, date, objet et description requis.');
        }
        $this->db->table('incident')->insert([
            'id_employe' => (int) $d['id_employe'], 'date_incident' => $d['date_incident'], 'objet' => trim($d['objet']), 'description' => trim($d['description']),
            'gravite' => $d['gravite'] ?? 'MODEREE', 'categorie' => $d['categorie'] ?? 'AUTRE', 'id_signaleur' => $userId,
        ]);
        $id = (int) $this->db->insertID();
        Audit::log('CREATE', 'incident', $id, null, $d, 'Signalement disciplinaire');
        return $id;
    }

    public function demanderExplication(int $idIncident, string $dateLimite, string $texte, int $userId): void
    {
        $inc = $this->incident($idIncident);
        $today = date('Y-m-d');
        if ($dateLimite < $today) {
            throw new \RuntimeException('La date limite ne peut pas être dans le passé.');
        }
        $this->db->transBegin();
        try {
            [$ok, $msg] = Transitions::appliquer('incident', $idIncident, 'EXPLICATIONS', 'Demande d’explications');
            if (! $ok) {
                throw new \RuntimeException($msg);
            }
            $this->db->table('demande_explication')->insert([
                'id_incident' => $idIncident, 'date_demande' => $today, 'date_limite' => $dateLimite, 'texte' => trim($texte) ?: 'Merci de fournir vos explications écrites.',
                'date_notification' => date('Y-m-d H:i:s'),
            ]);
            NotificationService::versEmploye((int) $inc['id_employe'], 'Demande d’explications', 'Merci de répondre avant le ' . $dateLimite . ' : ' . $inc['objet'], '/employe/discipline');
            Audit::log('CREATE', 'demande_explication', $idIncident, null, ['date_limite' => $dateLimite]);
            $this->fin();
        } catch (\Throwable $t) {
            $this->db->transRollback();
            throw $t;
        }
    }

    /**
     * Enregistre la réponse (par l'employé ou saisie papier par la RH). $fichier : UploadedFile|null (≤ 2 Mo, pdf/jpg/png).
     * en_retard = réponse reçue après date_limite.
     */
    public function enregistrerReponse(int $idIncident, string $texte, ?string $dateReponse, $fichier, int $userId): void
    {
        $inc = $this->incident($idIncident);
        $dem = $this->db->table('demande_explication')->where('id_incident', $idIncident)->get()->getRowArray();
        if (! $dem || $inc['statut'] !== 'EXPLICATIONS') {
            throw new \RuntimeException('Aucune demande d’explications en attente de réponse pour cet incident.');
        }
        if (trim($texte) === '') {
            throw new \RuntimeException('Le texte de la réponse est obligatoire.');
        }
        $dateReponse = $dateReponse ?: date('Y-m-d');
        $piece = ['piece_jointe_nom' => null, 'piece_jointe_mime' => null, 'piece_jointe_donnees' => null];
        if ($fichier && $fichier->isValid() && $fichier->getSize() > 0) {
            if ($fichier->getSize() > self::PIECE_MAX) {
                throw new \RuntimeException('Pièce jointe trop volumineuse (2 Mo maximum).');
            }
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($fichier->getTempName());
            if (! in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true)) {
                throw new \RuntimeException('Format de pièce jointe non autorisé (pdf, jpg, png).');
            }
            $piece = ['piece_jointe_nom' => $fichier->getClientName(), 'piece_jointe_mime' => $mime, 'piece_jointe_donnees' => file_get_contents($fichier->getTempName())];
        }
        $this->db->transBegin();
        try {
            $this->db->table('reponse_explication')->insert([
                'id_incident' => $idIncident, 'date_reponse' => $dateReponse, 'texte' => trim($texte), 'en_retard' => (int) ($dateReponse > $dem['date_limite']),
                'id_auteur' => $userId,
            ] + $piece);
            [$ok, $msg] = Transitions::appliquer('incident', $idIncident, 'REPONSE', 'Réponse reçue');
            if (! $ok) {
                throw new \RuntimeException($msg);
            }
            NotificationService::versRh('Réponse reçue', 'Réponse à la demande d’explications : ' . $inc['objet'], '/rh/discipline/incidents/' . $idIncident);
            Audit::log('CREATE', 'reponse_explication', (int) $this->db->insertID(), null, ['incident' => $idIncident, 'en_retard' => $dateReponse > $dem['date_limite']]);
            $this->fin();
        } catch (\Throwable $t) {
            $this->db->transRollback();
            throw $t;
        }
    }

    public function commission(int $idIncident, ?string $date, ?string $commentaire): void
    {
        $this->incident($idIncident);
        if (! $this->conditionRemplie($idIncident)) {
            throw new \RuntimeException('La commission exige une réponse reçue ou un délai de réponse dépassé.');
        }
        [$ok, $msg] = Transitions::appliquer('incident', $idIncident, 'COMMISSION', $commentaire, ['date_commission' => $date ?: date('Y-m-d'), 'commentaire_commission' => $commentaire]);
        if (! $ok) {
            throw new \RuntimeException($msg);
        }
    }

    /** Condition de sanction : demande notifiée ET (réponse OU délai dépassé). */
    public function conditionRemplie(int $idIncident): bool
    {
        $dem = $this->db->table('demande_explication')->where('id_incident', $idIncident)->get()->getRowArray();
        if (! $dem || empty($dem['date_notification'])) {
            return false;
        }
        $rep = $this->db->table('reponse_explication')->where('id_incident', $idIncident)->countAllResults() > 0;
        return $rep || $dem['date_limite'] < date('Y-m-d');
    }

    /** Types de sanction autorisés pour la gravité de l'incident. */
    public function typesAutorises(string $gravite): array
    {
        return array_column($this->db->table('gravite_sanction_autorisee')->where('gravite', $gravite)->get()->getResultArray(), 'type_sanction');
    }

    /** Sanctions de l'employé dans la fenêtre de récidive. */
    public function recidive(int $idEmploye): array
    {
        $m = (int) ParametreRh::get('DISC_RECIDIVE_MOIS', 12);
        return $this->db->query(
            "SELECT s.type_sanction, s.date_decision, i.objet FROM sanction s JOIN incident i ON i.id_incident = s.id_incident
             WHERE i.id_employe = ? AND s.statut != 'ANNULEE' AND s.date_decision >= ? ORDER BY s.date_decision DESC",
            [$idEmploye, date('Y-m-d', strtotime("-$m months"))]
        )->getResultArray();
    }

    /** Décision : classement sans suite ou sanction (LICENCIEMENT → rupture de contrat). */
    public function decider(int $idIncident, array $d, int $userId): void
    {
        $inc = $this->incident($idIncident);
        if (! $this->conditionRemplie($idIncident)) {
            throw new \RuntimeException('Décision impossible : une demande d’explications notifiée et (une réponse ou un délai dépassé) sont requis.');
        }
        $this->db->transBegin();
        try {
            if (($d['decision'] ?? '') === 'CLASSE_SANS_SUITE') {
                [$ok, $msg] = Transitions::appliquer('incident', $idIncident, 'CLOTURE', 'Classé sans suite');
                if (! $ok) {
                    throw new \RuntimeException($msg);
                }
                $this->db->table('incident')->where('id_incident', $idIncident)->update(['decision_finale' => 'CLASSE_SANS_SUITE']);
                $this->fin();
                return;
            }
            $type = (string) ($d['type_sanction'] ?? '');
            if (! in_array($type, $this->typesAutorises($inc['gravite']), true)) {
                throw new \RuntimeException('Type de sanction non autorisé pour la gravité « ' . $inc['gravite'] . ' ».');
            }
            $motif = trim((string) ($d['motif'] ?? ''));
            $dateDec = (string) ($d['date_decision'] ?? date('Y-m-d'));
            $dateEff = (string) ($d['date_effet'] ?? $dateDec);
            $nb = (int) ($d['nb_jours'] ?? 0);
            if ($motif === '') {
                throw new \RuntimeException('Le motif de la sanction est obligatoire.');
            }
            if ($type === 'MISE_A_PIED' && $nb < 1) {
                throw new \RuntimeException('Le nombre de jours de mise à pied doit être au moins 1.');
            }
            [$ok, $msg] = Transitions::appliquer('incident', $idIncident, 'DECIDE', 'Décision : sanction');
            if (! $ok) {
                throw new \RuntimeException($msg);
            }
            $this->db->table('sanction')->insert([
                'id_incident' => $idIncident, 'type_sanction' => $type, 'motif' => $motif, 'date_decision' => $dateDec, 'date_effet' => $dateEff,
                'id_decideur' => $userId, 'statut' => 'NOTIFIEE', 'nb_jours' => $type === 'MISE_A_PIED' ? $nb : 0,
            ]);
            $idS = (int) $this->db->insertID();
            [$ok, $msg] = Transitions::appliquer('incident', $idIncident, 'SANCTION', null);
            if (! $ok) {
                throw new \RuntimeException($msg);
            }
            $this->db->table('incident')->where('id_incident', $idIncident)->update(['decision_finale' => 'SANCTION']);
            if ($type === 'LICENCIEMENT') {
                // Même création que la rupture manuelle ; refus clair si une rupture existe déjà
                (new RuptureService())->creer([
                    'id_employe' => $inc['id_employe'], 'motif' => 'LICENCIEMENT_FAUTE', 'date_notification' => $dateDec, 'date_sortie' => $dateEff,
                    'duree_preavis_jours' => 0, 'id_sanction' => $idS, 'statut' => 'EN_COURS', 'motif_detail' => 'Licenciement disciplinaire : ' . $motif,
                ], $userId);
            }
            NotificationService::versEmploye((int) $inc['id_employe'], 'Sanction notifiée', $type . ' à compter du ' . $dateEff . '. Vous pouvez la contester depuis votre espace.', '/employe/discipline');
            Audit::log('CREATE', 'sanction', $idS, null, ['type' => $type, 'nb_jours' => $nb], $motif);
            $this->fin();
        } catch (\Throwable $t) {
            $this->db->transRollback();
            throw $t;
        }
    }

    /** Changement de statut de la sanction (contester par l'employé, exécutoire/annulée par la RH). */
    public function statutSanction(int $idSanction, string $vers, ?string $commentaire = null): void
    {
        $s = $this->db->table('sanction s')->select('s.*, i.id_employe')->join('incident i', 'i.id_incident = s.id_incident')->where('s.id_sanction', $idSanction)->get()->getRowArray();
        if (! $s) {
            throw new \RuntimeException('Sanction introuvable.');
        }
        $this->db->transBegin();
        try {
            $extra = $vers === 'CONTESTEE' ? ['commentaire_contestation' => $commentaire] : [];
            [$ok, $msg] = Transitions::appliquer('sanction', $idSanction, $vers, $commentaire, $extra);
            if (! $ok) {
                throw new \RuntimeException($msg);
            }
            if (in_array($vers, ['EXECUTOIRE', 'ANNULEE'], true)) {
                Transitions::appliquer('incident', (int) $s['id_incident'], 'CLOTURE', 'Sanction ' . $vers);
            }
            if ($vers === 'CONTESTEE') {
                NotificationService::versRh('Sanction contestée', 'Une sanction a été contestée par l’employé.', '/rh/discipline/incidents/' . $s['id_incident']);
            }
            if ($vers === 'ANNULEE' && $s['type_sanction'] === 'MISE_A_PIED') {
                $this->regulariserMiseAPied($s);
            }
            $this->fin();
        } catch (\Throwable $t) {
            $this->db->transRollback();
            throw $t;
        }
    }

    /** Mise à pied annulée alors qu'elle a été retenue sur une paie validée : RAPPEL le mois suivant. */
    private function regulariserMiseAPied(array $s): void
    {
        $rows = $this->db->query(
            "SELECT b.retenue_mise_a_pied, p.mois, p.annee FROM bulletin_paie b JOIN periode_paie p ON p.id_periode = b.id_periode
             WHERE b.id_employe = ? AND b.retenue_mise_a_pied > 0 AND b.statut IN ('VALIDE','PAIE') ORDER BY p.annee DESC, p.mois DESC",
            [$s['id_employe']]
        )->getResultArray();
        if (! $rows) {
            return;
        }
        $total = array_sum(array_column($rows, 'retenue_mise_a_pied'));
        $suivant = date('Y-m', strtotime(sprintf('%04d-%02d-01 +1 month', $rows[0]['annee'], $rows[0]['mois'])));
        $per = PeriodePaieService::assurer((int) substr($suivant, 5, 2), (int) substr($suivant, 0, 4));
        $rub = $this->db->table('rubrique_paie')->where('code', 'RAPPEL')->get()->getRow('id_rubrique');
        if ($per['verrouillee'] || ! $rub) {
            return;
        }
        $this->db->query(
            'INSERT INTO variable_paie (id_employe, id_periode, id_rubrique, nombre, montant, motif) VALUES (?,?,?,1,?,?)
             ON CONFLICT(id_employe, id_periode, id_rubrique) DO UPDATE SET montant = montant + excluded.montant',
            [$s['id_employe'], $per['id_periode'], $rub, $total, 'Annulation de la mise à pied (sanction #' . $s['id_sanction'] . ')']
        );
        Audit::log('CREATE', 'variable_paie', null, null, ['rappel' => $total], 'Régularisation mise à pied annulée');
    }

    private function incident(int $id): array
    {
        $i = $this->db->table('incident')->where('id_incident', $id)->get()->getRowArray();
        if (! $i) {
            throw new \RuntimeException('Incident introuvable.');
        }
        return $i;
    }

    private function fin(): void
    {
        if (! $this->db->transStatus()) {
            throw new \RuntimeException('Erreur base de données.');
        }
        $this->db->transCommit();
    }
}
