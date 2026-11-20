<?php

namespace App\Models;

use CodeIgniter\Model;

class PosteModel extends Model
{
    protected $table = 'poste';
    protected $primaryKey = 'id_poste';
    protected $returnType = 'array';
    protected $allowedFields = [
        'id_departement',
        'libelle',
        'description',
        'statut',
        'cree_par',
    ];

    public function forUser(int $userId, ?int $departmentId = null): array
    {
        $builder = $this->select('poste.*, departement.nom AS departement_nom')
            ->join('departement', 'departement.id_departement = poste.id_departement')
            ->join(
                'utilisateur_departement',
                'utilisateur_departement.id_departement = poste.id_departement'
            )
            ->where('utilisateur_departement.id_utilisateur', $userId)
            ->where('departement.actif', true)
            ->orderBy('poste.libelle', 'ASC');

        if ($departmentId !== null) {
            $builder->where('poste.id_departement', $departmentId);
        }

        return $builder->findAll();
    }

    public function findAuthorized(int $postId, int $userId): ?array
    {
        return $this->select('poste.*, departement.nom AS departement_nom')
            ->join('departement', 'departement.id_departement = poste.id_departement')
            ->join(
                'utilisateur_departement',
                'utilisateur_departement.id_departement = poste.id_departement'
            )
            ->where('poste.id_poste', $postId)
            ->where('utilisateur_departement.id_utilisateur', $userId)
            ->first();
    }
}
