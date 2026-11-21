<?php
namespace App\Controllers\Rh;

use App\Libraries\Audit;
use App\Libraries\Transitions;
use App\Models\PointageModel;
use App\Services\TempsTravailService;

class TempsTravailController extends RhController
{
    public function index()
    {
        return view('rh/temps_travail/index', [
            'pointages' => (new PointageModel())->recent(),
        ]);
    }

    public function pointer()
    {
        $post = $this->request->getPost();
        foreach (['heure_entree', 'heure_sortie'] as $champ) {
            if (!empty($post[$champ])) {
                $post[$champ] = str_replace('T', ' ', $post[$champ]);
                if (strlen($post[$champ]) === 16) {
                    $post[$champ] .= ':00';
                }
            }
        }
        $rules = [
            'id_employe' => 'required|is_natural_no_zero',
            'date_pointage' => 'required|valid_date[Y-m-d]',
            'heure_entree' => 'permit_empty|valid_date[Y-m-d H:i:s]',
            'heure_sortie' => 'permit_empty|valid_date[Y-m-d H:i:s]',
        ];
        if (!$this->validateData($post, $rules)) {
            return redirect()->back()->withInput()->with('erreur', implode(' ', $this->validator->getErrors()));
        }
        try {
            (new TempsTravailService())->enregistrerPointage($post);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('erreur', $e->getMessage());
        }
        return redirect()->to(site_url('rh/temps-travail'))->with('succes', 'Pointage enregistré.');
    }

    /** Liste des retards + formulaire de saisie manuelle. */
    public function retards()
    {
        $retards = $this->db->table('retard r')
            ->select('r.*, p.date_pointage, e.matricule, pe.nom, pe.prenom')
            ->join('pointage p', 'p.id_pointage = r.id_pointage')
            ->join('employe e', 'e.id_employe = p.id_employe')
            ->join('personne pe', 'pe.id_personne = e.id_personne')
            ->orderBy('p.date_pointage', 'DESC')->get()->getResultArray();
        return view('rh/paie/retards', ['titre' => 'Retards', 'retards' => $retards, 'employes' => $this->employes()]);
    }

    /** Saisie manuelle : minutes OU heure d'arrivée réelle. */
    public function creerRetard()
    {
        $idE = (int) $this->request->getPost('id_employe');
        $date = (string) $this->request->getPost('date_pointage');
        if ($idE <= 0 || ! $this->validDate($date)) {
            return $this->ko('Employé et date requis.');
        }
        $svc = new TempsTravailService();
        $debut = $svc->debutPrevu($idE, $date);
        $minutes = (int) $this->request->getPost('minutes');
        $arrivee = (string) $this->request->getPost('heure_arrivee');
        if ($minutes <= 0 && $arrivee !== '') {
            $minutes = intdiv(strtotime("$date $arrivee") - strtotime("$date $debut"), 60);
        }
        if ($minutes <= 0) {
            return $this->ko('Le retard doit être strictement positif.');
        }
        $this->db->transBegin();
        try {
            $pt = $this->db->table('pointage')->where(['id_employe' => $idE, 'date_pointage' => $date])->get()->getRowArray();
            if ($pt) {
                $idPt = (int) $pt['id_pointage'];
            } else {
                $this->db->table('pointage')->insert([
                    'id_employe' => $idE, 'date_pointage' => $date, 'source' => 'MANUEL', 'statut' => 'OUVERT',
                    'heure_entree' => date('Y-m-d H:i:s', strtotime("$date $debut +$minutes minutes")), 'commentaire' => 'Retard saisi manuellement',
                ]);
                $idPt = (int) $this->db->insertID();
            }
            if ($this->db->table('retard')->where('id_pointage', $idPt)->countAllResults() > 0) {
                throw new \RuntimeException('Un retard existe déjà pour ce jour.');
            }
            $justifie = (int) (bool) $this->request->getPost('justifie');
            $this->db->table('retard')->insert([
                'id_pointage' => $idPt, 'minutes' => $minutes, 'motif' => trim((string) $this->request->getPost('motif')),
                'justifie' => $justifie, 'statut' => $justifie ? 'JUSTIFIE' : 'A_TRAITER', 'automatique' => 0,
                'date_justification' => $justifie ? date('Y-m-d H:i:s') : null,
            ]);
            $id = (int) $this->db->insertID();
            $this->db->table('anomalie_pointage')->insert([
                'id_pointage' => $idPt, 'id_employe' => $idE, 'date_anomalie' => $date, 'type_anomalie' => 'RETARD',
                'description' => "Retard de $minutes min",
            ]);
            Audit::log('CREATE', 'retard', $id, null, ['minutes' => $minutes], 'Saisie manuelle');
            if (! $this->db->transStatus()) {
                throw new \RuntimeException('Erreur base de données.');
            }
            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            return $this->ko($e->getMessage());
        }
        return $this->ok('Retard enregistré.');
    }

    public function justifier(int $id)
    {
        $txt = trim((string) $this->request->getPost('justificatif'));
        if ($txt === '') {
            return $this->ko('Justificatif requis.');
        }
        [$ok, $msg] = Transitions::appliquer('retard', $id, 'JUSTIFIE', null, ['justifie' => 1, 'justificatif' => $txt, 'date_justification' => date('Y-m-d H:i:s')]);
        return $ok ? $this->ok('Retard justifié.') : $this->ko($msg);
    }

    /** Valide un retard (justifié : non déduit ; non justifié : déduit de la paie). */
    public function validerRetard(int $id)
    {
        [$ok, $msg] = Transitions::appliquer('retard', $id, 'VALIDE', null, ['id_valideur' => $this->uid()]);
        return $ok ? $this->ok('Retard validé.') : $this->ko($msg);
    }
}
