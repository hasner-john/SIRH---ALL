<?php

namespace App\Models;

use CodeIgniter\Model;

class DepartementModel extends Model
{
    protected $table = 'departement';
    protected $primaryKey = 'id_departement';
    protected $returnType = 'array';
    protected $allowedFields = ['nom', 'description', 'actif'];

    public function forUser(int $userId): array
    {
        return $this->select('departement.*')
            ->join(
                'utilisateur_departement',
                'utilisateur_departement.id_departement = departement.id_departement'
            )
            ->where('utilisateur_departement.id_utilisateur', $userId)
            ->where('departement.actif', true)
            ->orderBy('departement.nom', 'ASC')
            ->findAll();
    }

    public function belongsToUser(int $departmentId, int $userId): bool
    {
        return $this->select('departement.id_departement')
            ->join(
                'utilisateur_departement',
                'utilisateur_departement.id_departement = departement.id_departement'
            )
            ->where('departement.id_departement', $departmentId)
            ->where('utilisateur_departement.id_utilisateur', $userId)
            ->where('departement.actif', true)
            ->first() !== null;
    }
}
