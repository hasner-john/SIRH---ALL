<?php
namespace App\Models;
use CodeIgniter\Model;
class HoraireModel extends Model
{
    protected $table = 'horaire';
    protected $primaryKey = 'id_horaire';
    protected $returnType = 'array';
    protected $allowedFields = ['id_regime', 'libelle', 'heure_debut', 'heure_fin', 'pause_minutes', 'actif'];
}
