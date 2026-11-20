<?php
namespace App\Models;
use CodeIgniter\Model;
class PlanningModel extends Model
{
    protected $table = 'planning';
    protected $primaryKey = 'id_planning';
    protected $returnType = 'array';
    protected $allowedFields = ['id_employe', 'id_horaire', 'date_planning', 'statut'];
}
