<?php
namespace App\Models;
use CodeIgniter\Model;
class PointageModel extends Model
{
    protected $table = 'pointage';
    protected $primaryKey = 'id_pointage';
    protected $returnType = 'array';
    protected $allowedFields = ['id_employe', 'date_pointage', 'heure_entree', 'heure_sortie', 'source', 'statut', 'commentaire'];
    public function recent(int $limit = 50): array
    {
        return $this->orderBy('date_pointage', 'DESC')->findAll($limit);
    }
}
