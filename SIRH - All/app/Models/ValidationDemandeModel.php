<?php

namespace App\Models;

use CodeIgniter\Model;

class ValidationDemandeModel extends Model
{
    protected $table = 'validation_demande';
    protected $primaryKey = 'id_validation';
    protected $returnType = 'array';

    protected $allowedFields = [
        'id_demande',
        'id_validateur',
        'type_validation',
        'decision',
        'commentaire',
        'date_decision'
    ];

    protected $useTimestamps = false;

    public function EnregistrerValidation($data)
    {
        return $this->insert($data);
    }

   public function getValidateur($id_demande, $decision)
{
    return $this->db->table('validation_demande')
        ->select('id_validateur')
        ->where('id_demande', $id_demande)
        ->where('type_validation', 'RH')
        ->where('decision', $decision)
        ->orderBy('date_decision', 'DESC')
        ->orderBy('id_validation', 'DESC')
        ->get()
        ->getRowArray();
}
}