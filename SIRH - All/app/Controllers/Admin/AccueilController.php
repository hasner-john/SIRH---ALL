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
            'liens' => $this->liens(),
        ]);
    }

    /** Grille de pages de test, par section. Facile à compléter : une ligne = un bouton. */
    private function liens(): array
    {
        $l = [];
        $add = static function (string $section, array $pages) use (&$l) {
            foreach ($pages as $label => $url) {
                $l[] = ['section' => $section, 'label' => $label, 'url' => $url];
            }
        };
        $add('RH recrutement', ['Conditions' => 'rh/conditions', 'Demandes' => 'rh/demandes', 'Offres' => 'rh/offres', 'Entretiens' => 'rh/entretiens', 'Tests' => 'rh/tests', 'Évaluations candidats' => 'rh/evaluations', 'Requêtes' => 'rh/requetes']);
        $add('RH dossiers', ['Employés' => 'rh/employes', 'Embauche directe' => 'rh/employes/nouveau', 'Congés' => 'rh/conges', 'Temps de travail' => 'rh/temps-travail']);
        $add('RH paie', ['Modules avancés (bulletins)' => 'rh/modules-avances', 'Périodes de paie' => 'rh/paie/periodes', 'Variables / indemnités' => 'rh/paie/variables', 'Heures supplémentaires' => 'rh/paie/heures-supplementaires', 'Retards' => 'rh/temps-travail/retards']);
        $add('RH évaluation / formation', ['Compétences' => 'rh/competences', 'Performance' => 'rh/performance', 'Formations' => 'rh/formations', 'Propositions de formation' => 'rh/formations/propositions', 'Assigner une formation' => 'rh/formations/assigner', 'Apport des formations' => 'rh/formations/apport', 'Paramètres RH' => 'rh/parametres']);
        $add('RH carrière', ['Mouvements' => 'rh/carriere', 'Nouveau mouvement' => 'rh/carriere/mouvement', 'Propositions de carrière' => 'rh/carriere/propositions']);
        $add('RH discipline', ['Incidents' => 'rh/discipline/incidents', 'Sanctions' => 'rh/discipline/sanctions', 'Réponses des employés' => 'rh/discipline/reponses']);
        $add('RH ruptures', ['Ruptures et STC' => 'rh/ruptures']);
        $add('Direction', ['Accueil' => 'direction', 'Offres' => 'direction/offres', 'Candidatures' => 'direction/candidatures', 'Demandes' => 'direction/demandes', 'Embauche directe' => 'direction/employes/nouveau']);
        $add('Département', ['Accueil' => 'departement', 'Postes' => 'departement/postes', 'Demandes' => 'departement/demandes']);
        $add('Candidat', ['Offres' => 'candidat/offres', 'Mes candidatures' => 'candidat/mes-candidatures', 'Requêtes' => 'candidat/requetes', 'Contrats' => 'candidat/contrats']);
        $add('Employé', ['Accueil' => 'employe', 'Notifications' => 'employe/notifications', 'Formations' => 'employe/formations', 'Discipline' => 'employe/discipline', 'Bulletins' => 'employe/bulletins']);
        $dev = [];
        foreach (['RH', 'DIRECTION', 'DEPARTEMENT', 'CANDIDAT', 'FINANCE', 'EMPLOYE', 'EMPLOYE1', 'EMPLOYE2', 'ADMINISTRATEUR'] as $r) {
            $dev['Se connecter : ' . $r] = 'dev/login-as/' . $r;
        }
        $dev['Déconnexion'] = 'dev/logout';
        $add('Outils dev', $dev);
        return $l;
    }
}
