<?php
namespace App\Models;
use CodeIgniter\Model;
class HeureSupplementaireModel extends Model
{
    protected $table = 'heure_supplementaire';
    protected $primaryKey = 'id_heure_supplementaire';
    protected $returnType = 'array';
    protected $allowedFields = ['id_employe', 'date_travail', 'heures', 'motif', 'statut', 'id_validateur'];
}
