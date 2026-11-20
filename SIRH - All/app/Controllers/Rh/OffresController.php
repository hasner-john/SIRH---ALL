<?php

namespace App\Controllers\Rh;

use App\Controllers\BaseController;
use App\Models\OffreEmploiModel;
use App\Services\OffreEmploiService;
use CodeIgniter\Exceptions\PageNotFoundException;

class OffresController extends BaseController
{
    /**
     * Afficher les demandes de recrutement
     * qui peuvent donner lieu à une offre.
     */
    public function demandesEligibles()
    {
        $offreModel = new OffreEmploiModel();

        return view('rh/offres/demandes-eligibles', [
            'demandes' => $offreModel->getDemandesEligibles()
        ]);
    }

    /**
     * Afficher le formulaire de création.
     */
    public function creer($idDemande = null)
    {
        $offreModel = new OffreEmploiModel();

        if ($idDemande === null) {
            return redirect()->to(site_url('rh/offres/demandes'));
        }

        $demande = $offreModel->getDemandeValidee($idDemande);

        if (!$demande) {
            throw PageNotFoundException::forPageNotFound(
                'Demande de recrutement introuvable ou non éligible.'
            );
        }

        return view('rh/offres/creer', [
            'demande' => $demande
        ]);
    }

    /**
     * Enregistrer une nouvelle offre.
     */
    public function enregistrer()
    {
        $idDemande = (int) $this->request->getPost('id_demande');
        $titre = trim($this->request->getPost('titre') ?? '');
        $description = trim($this->request->getPost('description') ?? '');
        $dateLimite = $this->request->getPost('date_limite') ?: null;

        $service = new OffreEmploiService();

        $idOffre = $service->creerOffre(
            $idDemande,
            $titre,
            $description,
            $dateLimite
        );

        if (!$idOffre) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Impossible de créer l’offre. Vérifiez les informations saisies.');
        }

        return redirect()->to(site_url('rh/offres'))
            ->with('success', 'L’offre a été créée en brouillon.');
    }

    /**
     * Afficher toutes les offres pour le RH.
     */
    public function index()
    {
        $offreModel = new OffreEmploiModel();

        $offres = $offreModel
            ->orderBy('date_creation', 'DESC')
            ->findAll();

        return view('rh/offres/liste', [
            'offres' => $offres
        ]);
    }

    /**
     * Afficher les détails d'une offre.
     */
    public function show($id)
    {
        $offreModel = new OffreEmploiModel();
        $offre = $offreModel->getOffreAvecDetails($id);

        if (!$offre) {
            throw PageNotFoundException::forPageNotFound(
                'Offre introuvable.'
            );
        }

        return view('rh/offres/detail', [
            'offre' => $offre
        ]);
    }

    /**
     * Soumettre une offre à la Direction.
     */
    public function soumettre($id)
    {
        $service = new OffreEmploiService();

        if (!$service->soumettreOffre((int) $id)) {
            return redirect()->back()
                ->with('error', 'Impossible de soumettre cette offre.');
        }

        return redirect()->to(site_url('rh/offres'))
            ->with('success', 'L’offre a été soumise pour validation.');
    }

    /**
     * Publier une offre validée.
     */
    public function publier($id)
    {
        $service = new OffreEmploiService();

        if (!$service->publierOffre((int) $id)) {
            return redirect()->back()
                ->with('error', 'Impossible de publier cette offre.');
        }

        return redirect()->to(site_url('rh/offres'))
            ->with('success', 'L’offre a été publiée.');
    }

    /**
     * Fermer une offre publiée.
     */
    public function fermer($id)
    {
        $service = new OffreEmploiService();

        if (!$service->fermerOffre((int) $id)) {
            return redirect()->back()
                ->with('error', 'Impossible de fermer cette offre.');
        }

        return redirect()->to(site_url('rh/offres'))
            ->with('success', 'L’offre a été fermée.');
    }
}
