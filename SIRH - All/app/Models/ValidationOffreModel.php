<?php

namespace App\Models;

use CodeIgniter\Model;

class ValidationOffreModel extends Model
{
    protected $table = 'validation_offre';
    protected $primaryKey = 'id_validation_offre';
    protected $returnType = 'array';

    protected $allowedFields = [
        'id_offre',
        'id_validateur',
        'decision',
        'commentaire',
        'date_decision',
    ];

    protected $useTimestamps = false;
}
