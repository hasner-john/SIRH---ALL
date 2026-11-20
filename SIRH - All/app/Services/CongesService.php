<?php
namespace App\Services;

use App\Models\DemandeCongeModel;
use App\Models\SoldeCongeModel;
use CodeIgniter\Database\BaseConnection;

class CongesService
{
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    public function creerDemande(array $data): int|false
    {
        $debut = $data['date_debut'] ?? '';
        $fin = $data['date_fin'] ?? '';
        $jours = (float) ($data['nombre_jours'] ?? 0);
        if (!$debut || !$fin || $fin < $debut || $jours <= 0) return false;
        $overlap = $this->db->table('demande_conge')
            ->where('id_employe', $data['id_employe'])
            ->whereIn('statut', ['EN_ATTENTE', 'VALIDE'])
            ->where("date_debut <= " . $this->db->escape($fin), null, false)
            ->where("date_fin >= " . $this->db->escape($debut), null, false)->countAllResults();
        if ($overlap > 0) return false;
        $solde = (new SoldeCongeModel())->where([
            'id_employe' => $data['id_employe'], 'id_type_conge' => $data['id_type_conge'],
            'annee' => (int) substr($debut, 0, 4),
        ])->first();
        if ($solde && ((float) $solde['acquis'] + (float) $solde['ajuste'] - (float) $solde['pris']) < $jours) return false;
        $model = new DemandeCongeModel();
        return $model->insert($data, true) ?: false;
    }

    public function decider(int $id, string $statut, ?int $validateur, string $commentaire = ''): bool
    {
        if (!in_array($statut, ['VALIDE', 'REFUSE'], true)) return false;
        $demande = (new DemandeCongeModel())->find($id);
        if (!$demande || $demande['statut'] !== 'EN_ATTENTE') return false;
        $this->db->transStart();
        (new DemandeCongeModel())->update($id, [
            'statut' => $statut, 'id_validateur' => $validateur,
            'commentaire_validation' => $commentaire, 'date_decision' => date('Y-m-d H:i:s'),
        ]);
        if ($statut === 'VALIDE') {
            $this->db->table('solde_conge')->where([
                'id_employe' => $demande['id_employe'], 'id_type_conge' => $demande['id_type_conge'],
                'annee' => (int) substr($demande['date_debut'], 0, 4),
            ])->set('pris', 'pris + ' . (float) $demande['nombre_jours'], false)->update();
        }
        $this->db->transComplete();
        return $this->db->transStatus();
    }
}
