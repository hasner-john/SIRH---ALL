<?php

namespace App\Models;

use CodeIgniter\Model;

class HistoriqueConditionContratModel extends Model
{
    protected $table = 'historique_condition_contrat';
    protected $primaryKey = 'id_historique';
    protected $returnType = 'array';

    protected $allowedFields = [
        'id_condition',
        'ancienne_valeur',
        'nouvelle_valeur',
        'ancien_statut',
        'nouveau_statut',
        'id_utilisateur',
        'commentaire',
        'date_action',
    ];

    protected $useTimestamps = false;

    public function enregistrer(
        int $idCondition,
        ?string $ancienneValeur,
        ?string $nouvelleValeur,
        ?string $ancienStatut,
        ?string $nouveauStatut,
        ?int $idUtilisateur,
        string $commentaire
    ): bool {
        return $this->insert([
            'id_condition' => $idCondition,
            'ancienne_valeur' => $ancienneValeur,
            'nouvelle_valeur' => $nouvelleValeur,
            'ancien_statut' => $ancienStatut,
            'nouveau_statut' => $nouveauStatut,
            'id_utilisateur' => $idUtilisateur,
            'commentaire' => $commentaire,
        ]) !== false;
    }
}
