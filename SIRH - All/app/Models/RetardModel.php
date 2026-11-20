<?php
namespace App\Models;
use CodeIgniter\Model;
class RetardModel extends Model
{
    protected $table = 'retard';
    protected $primaryKey = 'id_retard';
    protected $returnType = 'array';
    protected $allowedFields = ['id_pointage', 'minutes', 'motif', 'justifie', 'statut'];
}
