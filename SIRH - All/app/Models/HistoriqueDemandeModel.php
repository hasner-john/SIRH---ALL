<?php

namespace App\Models;

use CodeIgniter\Model;

class HistoriqueDemandeModel extends Model
{
    protected $table = 'historique_demande';
    protected $primaryKey = 'id_historique';
    protected $returnType = 'array';

    protected $allowedFields = [
        'id_demande',
        'ancien_statut',
        'nouveau_statut',
        'id_utilisateur',
        'commentaire',
        'date_action'
    ];

    protected $useTimestamps = false;

    public function EnregistrerHistorique($data)
    {
        return $this->insert($data);
    }
}