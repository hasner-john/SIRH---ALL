<?php
namespace App\Models;
use CodeIgniter\Model;

class DroitCongeModel extends Model
{
    protected $table = 'droit_conge';
    protected $primaryKey = 'id_droit_conge';
    protected $returnType = 'array';
    protected $allowedFields = ['id_type_conge', 'code_role', 'anciennete_mois', 'jours_annuels'];
    protected $useTimestamps = false;
}
