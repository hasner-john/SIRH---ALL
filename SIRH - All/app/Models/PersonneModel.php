<?php

namespace App\Models;

use CodeIgniter\Model;

/** Table `personne` : identité liée à un compte utilisateur (1 pour 1). */
class PersonneModel extends Model
{
    protected $table            = 'personne';
    protected $primaryKey       = 'id_personne';
    protected $returnType       = 'object';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = false;

    protected $allowedFields = [
        'id_utilisateur',
        'nom',
        'prenom',
        'telephone',
        'adresse',
        'date_naissance',
    ];

    public function findByUtilisateur(int $idUtilisateur): ?object
    {
        return $this->where('id_utilisateur', $idUtilisateur)->first();
    }
}