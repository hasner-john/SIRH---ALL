<?php

namespace App\Models;

use CodeIgniter\Model;

class HistoriqueOffreEmploiModel extends Model
{
    protected $table = 'historique_offre_emploi';
    protected $primaryKey = 'id_historique';
    protected $returnType = 'array';

    protected $allowedFields = [
        'id_offre',
        'ancien_statut',
        'nouveau_statut',
        'id_utilisateur',
        'commentaire',
        'date_action'
    ];

    protected $useTimestamps = false;
}
