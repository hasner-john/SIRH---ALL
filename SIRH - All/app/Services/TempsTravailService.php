<?php
namespace App\Services;

use App\Models\AnomaliePointageModel;
use App\Models\PointageModel;
use App\Models\TempsTravailleModel;

class TempsTravailService
{
    public function enregistrerPointage(array $data): int
    {
        $pointages = new PointageModel();
        $id = $pointages->insert($data, true);
        if ($id === false) {
            throw new \RuntimeException('Pointage invalide.');
        }
        if (!empty($data['heure_entree']) && !empty($data['heure_sortie'])) {
            $debut = new \DateTime($data['heure_entree']);
            $fin = new \DateTime($data['heure_sortie']);
            $minutes = max(0, (int) (($fin->getTimestamp() - $debut->getTimestamp()) / 60));
            (new TempsTravailleModel())->insert([
                'id_pointage' => $id,
                'minutes_travaillees' => $minutes,
                'minutes_theoriques' => 480,
                'ecart_minutes' => $minutes - 480,
            ]);
        } else {
            (new AnomaliePointageModel())->insert([
                'id_pointage' => $id,
                'id_employe' => $data['id_employe'],
                'date_anomalie' => $data['date_pointage'],
                'type_anomalie' => empty($data['heure_entree']) ? 'ENTREE_MANQUANTE' : 'SORTIE_MANQUANTE',
                'description' => 'Pointage incomplet',
            ]);
        }
        return (int) $id;
    }
}
