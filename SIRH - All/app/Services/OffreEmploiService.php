<?php

namespace App\Services;

use App\Models\OffreEmploiModel;
use App\Models\ValidationOffreModel;
use App\Models\HistoriqueOffreEmploiModel;

class OffreEmploiService
{
    protected $db;
    protected $offreModel;
    protected $validationModel;
    protected $historiqueModel;

    public function __construct()
    {
        $this->db = \Config\Database::connect();

        $this->offreModel = new OffreEmploiModel();
        $this->validationModel = new ValidationOffreModel();
        $this->historiqueModel = new HistoriqueOffreEmploiModel();}

    /**
     * Enregistrer un changement de statut et son historique.
     * À appeler pendant une transaction.
     */
    private function changerStatut(
        array $offre,
        string $nouveauStatut,
        ?int $idUtilisateur,
        ?string $commentaire = null
    ) {
        $idOffre = $offre['id_offre'];
        $ancienStatut = $offre['statut'];

        $this->offreModel->update($idOffre, [
            'statut' => $nouveauStatut
        ]);

        if (!$this->db->transStatus()) {
            throw new \RuntimeException(
                'Erreur lors de la modification de l\'offre.'
            );
        }

        $this->historiqueModel->insert([
            'id_offre' => $idOffre,
            'ancien_statut' => $ancienStatut,
            'nouveau_statut' => $nouveauStatut,
            'id_utilisateur' => $idUtilisateur,
            'commentaire' => $commentaire
        ]);

        if (!$this->db->transStatus()) {
            throw new \RuntimeException(
                'Erreur lors de l\'enregistrement de l\'historique.'
            );
        }
    }

    /**
     * Créer une offre à partir d'une demande validée.
     */
    public function creerOffre(
        int $idDemande,
        string $titre,
        string $description,
        ?string $dateLimite = null
    ) {

        $idUtilisateur = session()->get('id_utilisateur');

        if (empty($idUtilisateur)) {
            throw new \RuntimeException(
                'Utilisateur non connecté.'
            );
        }

        $titre = trim($titre);
        $description = trim($description);

        if ($titre === '' || $description === '') {
            throw new \InvalidArgumentException(
                'Le titre et la description sont obligatoires.'
            );
        }

        if (mb_strlen($titre) > 180) {
            throw new \InvalidArgumentException(
                'Le titre ne doit pas dépasser 180 caractères.'
            );
        }

        if ($dateLimite !== null && $dateLimite !== '') {
            $date = \DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $dateLimite
            );

            if (!$date || $date->format('Y-m-d') !== $dateLimite) {
                throw new \InvalidArgumentException(
                    'La date limite est invalide.'
                );
            }
        } else {
            $dateLimite = null;
        }

        $demande = $this->offreModel->getDemandeValidee($idDemande);

        if (!$demande) {
            throw new \RuntimeException(
                'Demande introuvable, non validée ou poste inactif.'
            );
        }

        $this->db->transBegin();

        try {
            $this->offreModel->insert([
                'id_demande' => $idDemande,
                'titre' => $titre,
                'description' => $description,
                'date_publication' => null,
                'date_limite' => $dateLimite,
                'statut' => 'BROUILLON',
                'cree_par' => $idUtilisateur
            ]);

            if (!$this->db->transStatus()) {
                throw new \RuntimeException(
                    'Erreur lors de la création de l\'offre.'
                );
            }

            $idOffre = $this->offreModel->getInsertID();

            $this->historiqueModel->insert([
                'id_offre' => $idOffre,
                'ancien_statut' => null,
                'nouveau_statut' => 'BROUILLON',
                'id_utilisateur' => $idUtilisateur,
                'commentaire' => 'Création de l\'offre.'
            ]);

            if (!$this->db->transStatus()) {
                throw new \RuntimeException(
                    'Erreur lors de l\'enregistrement de l\'historique.'
                );
            }

            $this->db->transCommit();

            return $idOffre;

        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /**
     * Soumettre une offre à la Direction.
     */
    public function soumettreOffre(int $idOffre)
    {

        return $this->transitionner(
            $idOffre,
            'BROUILLON',
            'EN_ATTENTE',
            'Offre soumise pour validation.'
        );
    }

    /**
     * Valider ou refuser une offre.
     */
    public function deciderOffre(
        int $idOffre,
        string $decision,
        ?string $commentaire = null
    ) {

        if (!in_array($decision, ['VALIDE', 'REFUSEE'], true)) {
            throw new \InvalidArgumentException(
                'Décision invalide.'
            );
        }

        $idUtilisateur = session()->get('id_utilisateur');

        $offre = $this->offreModel->find($idOffre);

        if (!$offre) {
            throw new \RuntimeException('Offre introuvable.');
        }

        if ($offre['statut'] !== 'EN_ATTENTE') {
            throw new \RuntimeException(
                'Seule une offre en attente peut être décidée.'
            );
        }

        $this->db->transBegin();

        try {
            $this->changerStatut(
                $offre,
                $decision,
                $idUtilisateur,
                $commentaire
            );

            $this->validationModel->insert([
                'id_offre' => $idOffre,
                'id_validateur' => $idUtilisateur,
                'decision' => $decision,
                'commentaire' => $commentaire
            ]);

            if (!$this->db->transStatus()) {
                throw new \RuntimeException(
                    'Erreur lors de l\'enregistrement de la décision.'
                );
            }

            $this->db->transCommit();

            return true;

        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /**
     * Publier une offre validée.
     */
    public function publierOffre(int $idOffre)
    {

        $offre = $this->offreModel->find($idOffre);

        if (!$offre) {
            throw new \RuntimeException('Offre introuvable.');
        }

        if ($offre['statut'] !== 'VALIDE') {
            throw new \RuntimeException(
                'Seule une offre validée peut être publiée.'
            );
        }

        $datePublication = date('Y-m-d');

        if (
            $offre['date_limite'] !== null
            && $offre['date_limite'] < $datePublication
        ) {
            throw new \RuntimeException(
                'La date limite est déjà dépassée.'
            );
        }

        $this->db->transBegin();

        try {
            $this->offreModel->update($idOffre, [
                'date_publication' => $datePublication
            ]);

            if (!$this->db->transStatus()) {
                throw new \RuntimeException(
                    'Erreur lors de la préparation de la publication.'
                );
            }

            $offre['date_publication'] = $datePublication;

            $this->changerStatut(
                $offre,
                'PUBLIEE',
                session()->get('id_utilisateur'),
                'Publication de l\'offre.'
            );

            $this->db->transCommit();

            return true;

        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /**
     * Clôturer une offre publiée.
     */
    public function fermerOffre(int $idOffre)
    {
        return $this->transitionner(
            $idOffre,
            'PUBLIEE',
            'FERMEE',
            'Clôture de l\'offre.'
        );
    }

    /**
     * Gérer une transition simple de statut.
     */
    private function transitionner(
        int $idOffre,
        string $statutAttendu,
        string $nouveauStatut,
        string $commentaire
    ) {
        $idUtilisateur = session()->get('id_utilisateur');

        if (empty($idUtilisateur)) {
            throw new \RuntimeException(
                'Utilisateur non connecté.'
            );
        }

        $offre = $this->offreModel->find($idOffre);

        if (!$offre) {
            throw new \RuntimeException('Offre introuvable.');
        }

        if ($offre['statut'] !== $statutAttendu) {
            throw new \RuntimeException(
                'Transition impossible depuis le statut actuel.'
            );
        }

        $this->db->transBegin();

        try {
            $this->changerStatut(
                $offre,
                $nouveauStatut,
                $idUtilisateur,
                $commentaire
            );

            $this->db->transCommit();

            return true;

        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}
