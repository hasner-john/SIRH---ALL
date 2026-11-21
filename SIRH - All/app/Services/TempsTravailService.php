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
        if (!empty($data['heure_entree'])) {
            $this->detecterRetard((int) $id, (int) $data['id_employe'], (string) $data['date_pointage'], (string) $data['heure_entree']);
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

    /** Horaire prévu : planning du jour, sinon premier horaire actif du régime de l'employé. Retourne HH:MM[:SS]. */
    public function debutPrevu(int $idEmploye, string $date): string
    {
        $db = \Config\Database::connect();
        $h = $db->table('planning p')->select('h.heure_debut')->join('horaire h', 'h.id_horaire = p.id_horaire')
            ->where(['p.id_employe' => $idEmploye, 'p.date_planning' => $date])->where('p.statut !=', 'ANNULE')->get()->getRowArray();
        if (! $h) {
            $h = $db->table('employe e')->select('h.heure_debut')->join('horaire h', 'h.id_regime = e.id_regime AND h.actif = 1')
                ->where('e.id_employe', $idEmploye)->orderBy('h.id_horaire')->get(1)->getRowArray();
        }
        return $h['heure_debut'] ?? '08:00';
    }

    /** Création automatique du retard au pointage (seuil de tolérance en paramètre RH). */
    private function detecterRetard(int $idPointage, int $idEmploye, string $date, string $entree): void
    {
        $db = \Config\Database::connect();
        $minutes = intdiv(strtotime($entree) - strtotime($date . ' ' . $this->debutPrevu($idEmploye, $date)), 60);
        $tol = (int) \App\Libraries\ParametreRh::get('TOLERANCE_RETARD_MIN', 0);
        $existe = $db->table('retard')->where('id_pointage', $idPointage)->get()->getRowArray();
        if ($minutes > $tol) {
            $data = ['minutes' => $minutes, 'automatique' => 1, 'motif' => 'Retard détecté au pointage'];
            if ($existe) {
                if ($existe['statut'] === 'A_TRAITER') {
                    $db->table('retard')->where('id_retard', $existe['id_retard'])->update($data);
                }
            } else {
                $db->table('retard')->insert($data + ['id_pointage' => $idPointage, 'justifie' => 0, 'statut' => 'A_TRAITER']);
                $db->table('anomalie_pointage')->insert([
                    'id_pointage' => $idPointage, 'id_employe' => $idEmploye, 'date_anomalie' => $date,
                    'type_anomalie' => 'RETARD', 'description' => "Retard de $minutes min",
                ]);
            }
        } elseif ($existe && (int) $existe['automatique'] === 1 && $existe['statut'] === 'A_TRAITER') {
            $db->table('retard')->where('id_retard', $existe['id_retard'])->delete();
        }
    }
}
