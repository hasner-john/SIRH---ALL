<?php
namespace App\Models;
use CodeIgniter\Model;
class TempsTravailleModel extends Model
{
    protected $table = 'temps_travaille';
    protected $primaryKey = 'id_temps';
    protected $returnType = 'array';
    protected $allowedFields = ['id_pointage', 'minutes_travaillees', 'minutes_theoriques', 'ecart_minutes'];
}
