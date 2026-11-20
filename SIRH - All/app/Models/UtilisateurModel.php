<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Table `utilisateur` : compte de connexion (email, hash, rôle).
 * Les emails sont toujours stockés en minuscules (RG-UTIL-001).
 */
class UtilisateurModel extends Model
{
    protected $table            = 'utilisateur';
    protected $primaryKey       = 'id_utilisateur';
    protected $returnType       = 'object';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = false;

    protected $allowedFields = [
        'email',
        'mot_de_passe_hash',
        'role',
        'actif',
    ];

    public static function normaliserEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public function findByEmail(string $email): ?object
    {
        return $this->where('email', self::normaliserEmail($email))->first();
    }

    public function emailExiste(string $email): bool
    {
        return $this->where('email', self::normaliserEmail($email))->countAllResults() > 0;
    }
}