<?php
namespace App\Models;
use CodeIgniter\Model;

class AbsenceModel extends Model
{
    protected $table = 'absence';
    protected $primaryKey = 'id_absence';
    protected $returnType = 'array';
    protected $allowedFields = ['id_employe', 'date_debut', 'date_fin', 'type_absence', 'motif', 'justifiee', 'id_justificatif', 'cree_par'];
    protected $useTimestamps = false;
}
