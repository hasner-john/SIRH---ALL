<?php

namespace App\Controllers\Rh;

use App\Controllers\BaseController;

/**
 * Espace RH — Tsiky
 * Routes préfixées : /rh/*  | filtre role:RH
 */
class AccueilController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();

        return view('rh/accueil', [
            'titre' => 'Espace RH',
            'role'  => session()->get('role'),
            'indicateurs' => [
                'demandes' => (int) $db->table('demande_recrutement')->whereIn('statut', ['BROUILLON', 'EN_ATTENTE'])->countAllResults(),
                'candidatures' => (int) $db->table('candidature')->whereIn('statut', ['RECUE', 'PRESELECTIONNEE'])->countAllResults(),
                'entretiens' => (int) $db->table('entretien')->where('statut', 'PLANIFIE')->countAllResults(),
                'conges' => (int) $db->table('demande_conge')->where('statut', 'EN_ATTENTE')->countAllResults(),
                'employes' => (int) $db->table('employe')->where('statut', 'ACTIF')->countAllResults(),
                'bulletins' => (int) $db->table('bulletin_paie')->countAllResults(),
                'incidents' => (int) $db->table('incident')->whereNotIn('statut', ['CLOTURE'])->countAllResults(),
                'ruptures' => (int) $db->table('rupture_contrat')->whereIn('statut', ['OUVERT', 'EN_COURS'])->countAllResults(),
            ],
        ]);
    }
}
