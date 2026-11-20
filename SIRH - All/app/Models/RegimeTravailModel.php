<?php
namespace App\Models;
use CodeIgniter\Model;
class RegimeTravailModel extends Model
{
    protected $table = 'regime_travail';
    protected $primaryKey = 'id_regime';
    protected $returnType = 'array';
    protected $allowedFields = ['libelle', 'heures_hebdomadaires', 'actif'];
}
