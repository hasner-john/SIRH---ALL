<?php
namespace App\Models;
use CodeIgniter\Model;

class JustificatifCongeModel extends Model
{
    protected $table = 'justificatif_conge';
    protected $primaryKey = 'id_justificatif';
    protected $returnType = 'array';
    protected $allowedFields = ['id_demande_conge', 'nom_fichier', 'type_mime', 'donnees'];
    protected $useTimestamps = false;
}
