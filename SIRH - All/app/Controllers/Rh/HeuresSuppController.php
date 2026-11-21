<?php

declare(strict_types=1);

namespace App\Controllers\Rh;

use App\Libraries\Transitions;
use App\Services\HeureSuppService;

class HeuresSuppController extends RhController
{
    public function index()
    {
        $q = $this->db->table('heure_supplementaire h')->select('h.*, e.matricule, pe.nom, pe.prenom')
            ->join('employe e', 'e.id_employe = h.id_employe')->join('personne pe', 'pe.id_personne = e.id_personne');
        if ((int) $this->request->getGet('id_employe') > 0) {
            $q->where('h.id_employe', (int) $this->request->getGet('id_employe'));
        }
        if ($this->request->getGet('statut')) {
            $q->where('h.statut', (string) $this->request->getGet('statut'));
        }
        return view('rh/paie/heures_sup', [
            'titre' => 'Heures supplémentaires',
            'heures' => $q->orderBy('h.date_travail', 'DESC')->get()->getResultArray(),
            'employes' => $this->employes(),
            'filtre' => $this->request->getGet(),
        ]);
    }

    public function enregistrer()
    {
        $date = (string) $this->request->getPost('date_travail');
        $debut = (string) $this->request->getPost('heure_debut');
        $fin = (string) $this->request->getPost('heure_fin');
        $idE = (int) $this->request->getPost('id_employe');
        if ($idE <= 0 || ! $this->validDate($date) || $debut === '' || $fin === '') {
            return $this->ko('Employé, date et heures requis.');
        }
        try {
            $n = (new HeureSuppService())->enregistrer($idE, $date, $debut, $fin, trim((string) $this->request->getPost('motif')), $this->uid(), true);
        } catch (\Throwable $e) {
            return $this->ko($e->getMessage());
        }
        return $this->ok("Heures supplémentaires enregistrées ($n ligne(s)).");
    }

    public function decision(int $id)
    {
        $vers = (string) $this->request->getPost('decision') === 'VALIDE' ? 'VALIDE' : 'REFUSEE';
        [$ok, $msg] = Transitions::appliquer('heure_supplementaire', $id, $vers, null, ['id_validateur' => $this->uid()]);
        return $ok ? $this->ok('Décision enregistrée.') : $this->ko($msg);
    }

    /** Aperçu en direct (JSON) sous le formulaire. */
    public function apercu()
    {
        try {
            $a = (new HeureSuppService())->apercu((string) $this->request->getGet('date'), (string) $this->request->getGet('debut'), (string) $this->request->getGet('fin'));
        } catch (\Throwable) {
            return $this->response->setJSON(['erreur' => 'Saisie incomplète']);
        }
        return $this->response->setJSON($a);
    }
}
