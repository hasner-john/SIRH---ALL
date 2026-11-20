<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

/**
 * Espace Admin — technique / Danie
 * Routes préfixées : /admin/*  | filtre role:ADMINISTRATEUR
 */
class AccueilController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $stats = [
            'utilisateurs' => (int) $db->table('utilisateur')->countAllResults(),
            'employes' => (int) $db->table('employe')->where('statut', 'ACTIF')->countAllResults(),
            'departements' => (int) $db->table('departement')->where('actif', 1)->countAllResults(),
            'audits' => (int) $db->table('audit_rh')->countAllResults(),
        ];
        return view('admin/accueil', [
            'titre' => 'Administration',
            'role'  => session()->get('role'),
            'stats' => $stats,
        ]);
    }
}
