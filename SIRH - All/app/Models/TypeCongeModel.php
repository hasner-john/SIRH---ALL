<?php
namespace App\Models;
use CodeIgniter\Model;

class TypeCongeModel extends Model
{
    protected $table = 'type_conge';
    protected $primaryKey = 'id_type_conge';
    protected $returnType = 'array';
    protected $allowedFields = ['code', 'libelle', 'description', 'jours_annuels', 'justificatif_obligatoire', 'actif'];
    protected $useTimestamps = false;
}
