<?php
namespace App\Models;
use CodeIgniter\Model;
class AnomaliePointageModel extends Model
{
    protected $table = 'anomalie_pointage';
    protected $primaryKey = 'id_anomalie';
    protected $returnType = 'array';
    protected $allowedFields = ['id_pointage', 'id_employe', 'date_anomalie', 'type_anomalie', 'description', 'statut', 'date_resolution'];
}
