<?php

namespace App\Services;

use App\Models\DemandeModel;
use App\Models\HistoriqueDemandeModel;
use App\Models\ValidationDemandeModel;

class DemandeService
{
    protected $demandeModel;
    protected $historiqueDemandeModel;
    protected $validationDemandeModel;

    public function __construct(
        ?DemandeModel $demandeModel = null,
        ?HistoriqueDemandeModel $historiqueDemandeModel = null,
        ?ValidationDemandeModel $validationDemandeModel = null
    ) {
        $this->demandeModel = $demandeModel ?? new DemandeModel();
        $this->historiqueDemandeModel = $historiqueDemandeModel ?? new HistoriqueDemandeModel();
        $this->validationDemandeModel = $validationDemandeModel ?? new ValidationDemandeModel();
    }

    protected function getDatabase()
    {
        return \Config\Database::connect();
    }

    public function isTransitionAllowed($demande, $nouveau_statut, $changeur = null)
    {
        if (!$demande) {
            return false;
        }

        $transitions = [
            'BROUILLON' => ['EN_ATTENTE', 'REFUSEE'],
            'EN_ATTENTE' => ['VALIDE', 'REFUSEE', 'ANNULEE'],
            'ANNULEE' => []
        ];

        // Cas particulier : réouverture d'une demande refusée
        if (
            $demande['statut'] === 'REFUSEE'
            && $nouveau_statut === 'EN_ATTENTE'
            && $changeur !== null
        ) {
            $ancienValidateur = $this->validationDemandeModel
                ->getValidateur($demande['id_demande'], 'REFUSEE');

            return $ancienValidateur
                && $ancienValidateur['id_validateur'] == $changeur;
        }

        return in_array(
            $nouveau_statut,
            $transitions[$demande['statut']] ?? [],
            true
        );
    }

    public function updateStatutDemande($data)
    {
        log_message('debug', 'SESSION RH : ' . json_encode(session()->get()));
        $changeur = session()->get('id_utilisateur');

        // Vérifier les données obligatoires
        if (!isset($data['id_demande']) || !isset($data['nouveau_statut']) || !$changeur) {
            return false;
        }

        $demande = $this->demandeModel->getDemandeAvecDetails($data['id_demande']);

        if (!$demande) {
            return false;
        }

        // Vérifier si la transition est autorisée
        if (!$this->isTransitionAllowed($demande, $data['nouveau_statut'], $changeur)) {
            return false;
        }

        $ancien_statut = $demande['statut'];

        $decision = $data['nouveau_statut'];

        if ($ancien_statut === 'REFUSEE' && $data['nouveau_statut'] === 'EN_ATTENTE') {
            $decision = 'REOUVERTURE';
        }

        $historiqueData = [
            'id_demande' => $data['id_demande'],
            'ancien_statut' => $ancien_statut,
            'nouveau_statut' => $data['nouveau_statut'],
            'id_utilisateur' => $changeur,
            'commentaire' => $data['commentaire'] ?? null
        ];

        $validation = [
            'id_demande' => $data['id_demande'],
            'id_validateur' => $changeur,
            'type_validation' => strtoupper((string) session()->get('role')) === 'ADMINISTRATEUR'
                ? 'ADMINISTRATEUR'
                : 'RH',
            'decision' => $decision,
            'commentaire' => $data['commentaire'] ?? null
        ];

        $db = $this->getDatabase();
        $db->transBegin();

        try {
            $resultat = $this->demandeModel->updateStatutDemande($data['id_demande'], $data['nouveau_statut']);

            if (!$resultat) {
                throw new \RuntimeException('Echec de la mise à jour du statut.');
            }

            $resultat = $this->historiqueDemandeModel->EnregistrerHistorique($historiqueData);

            if (!$resultat) {
                throw new \RuntimeException('Echec de l’enregistrement de l’historique.');
            }

            $resultat = $this->validationDemandeModel->EnregistrerValidation($validation);

            if (!$resultat) {
                throw new \RuntimeException('Echec de l’enregistrement de la validation.');
            }

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Echec de la transaction.');
            }

            $db->transCommit();
            return true;
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', $e->getMessage());
            return false;
        }
    }
}
