<?php

namespace App\Models;

use CodeIgniter\Model;

class DemandeRecrutementModel extends Model
{
    protected $table = 'demande_recrutement';
    protected $primaryKey = 'id_demande';
    protected $returnType = 'array';
    protected $allowedFields = [
        'id_poste',
        'id_demandeur',
        'effectif_demande',
        'motif',
        'salaire_par_personne',
        'date_besoin',
        'statut',
        'date_modification',
    ];

    public function forUser(int $userId, ?string $status = null): array
    {
        $builder = $this->select(
                'demande_recrutement.*, poste.libelle AS poste_libelle, ' .
                'departement.nom AS departement_nom'
            )
            ->join('poste', 'poste.id_poste = demande_recrutement.id_poste')
            ->join('departement', 'departement.id_departement = poste.id_departement')
            ->join(
                'utilisateur_departement',
                'utilisateur_departement.id_departement = poste.id_departement'
            )
            ->where('utilisateur_departement.id_utilisateur', $userId)
            ->orderBy('demande_recrutement.date_creation', 'DESC');

        if ($status !== null && $status !== '') {
            $builder->where('demande_recrutement.statut', $status);
        }

        return $builder->findAll();
    }

    public function findAuthorized(int $id, int $userId): ?array
    {
        return $this->select(
                'demande_recrutement.*, poste.libelle AS poste_libelle, ' .
                'departement.nom AS departement_nom'
            )
            ->join('poste', 'poste.id_poste = demande_recrutement.id_poste')
            ->join('departement', 'departement.id_departement = poste.id_departement')
            ->join(
                'utilisateur_departement',
                'utilisateur_departement.id_departement = poste.id_departement'
            )
            ->where('demande_recrutement.id_demande', $id)
            ->where('utilisateur_departement.id_utilisateur', $userId)
            ->first();
    }
}
