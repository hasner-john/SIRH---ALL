<?php

namespace App\Controllers\Rh;

use App\Controllers\BaseController;
use App\Models\DemandeModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use App\Services\DemandeService;

class DemandesController extends BaseController
{
    public function index()
    {
        $demandeModel = new DemandeModel();
        $demandes = $demandeModel->getDemandesAvecDetails();

        return view('rh/demandes/liste-demandes', [
            'demandes' => $demandes
        ]);
    }

    public function show($id)
    {
        $demandeModel = new DemandeModel();
        $demande = $demandeModel->getDemandeAvecDetails($id);

        if (!$demande) {
            throw new PageNotFoundException("Demande non trouvée : $id");
        }

        return view('rh/demandes/detail-demande', [
            'demande' => $demande
        ]);
    }


    public function decision()
    {
        $data = [
            'id_demande'     => $this->request->getPost('id_demande'),
            'nouveau_statut' => $this->request->getPost('nouveau_statut'),
            'commentaire'    => trim($this->request->getPost('commentaire') ?? '')
        ];

        $service = new DemandeService();

        if (!$service->updateStatutDemande($data)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Impossible d’enregistrer la décision. Vérifiez le statut et vos autorisations.');
        }

        return redirect()
            ->to(site_url('rh/demandes'))
            ->with('success', 'La décision a été enregistrée avec succès.');
    }

    public function reouvrir()
    {
        $data = [
            'id_demande'     => $this->request->getPost('id_demande'),
            'nouveau_statut' => 'EN_ATTENTE',
            'commentaire'    => trim($this->request->getPost('commentaire') ?? '')
        ];

        $service = new DemandeService();

        if (!$service->updateStatutDemande($data)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Impossible de réouvrir la demande. Vérifiez vos autorisations.');
        }

        return redirect()
            ->to(site_url('rh/demandes'))
            ->with('success', 'La demande a été réouverte et remise en attente.');
    }
}
