<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 *
 * Structure par rôle — chaque groupe est protégé par le filtre `role:XXX`.
 * Les contrôleurs placeholder permettent à chaque membre de commencer
 * sans se bloquer sur l'auth (Danie finalisera AuthController).
 *
 * Rôles SQL : DEPARTEMENT | RH | DIRECTION | CANDIDAT | ADMINISTRATEUR | FINANCE
 */

// -------------------------------------------------------------------------
// Public (non authentifié)
// -------------------------------------------------------------------------
$routes->get('/', 'AuthController::connexion');
$routes->get('connexion', 'AuthController::connexion');
$routes->post('connexion', 'AuthController::checkConnexion');
$routes->get('inscription', 'AuthController::inscription');
$routes->post('inscription', 'AuthController::registerCandidat');
$routes->get('deconnexion', 'AuthController::deconnexion');

// -------------------------------------------------------------------------
// Espace Département — John
// -------------------------------------------------------------------------
$routes->group('departement', ['filter' => 'role:DEPARTEMENT'], static function ($routes) {
    $routes->get('/', 'Departement\AccueilController::index');
    $routes->get('postes', 'Departement\AccueilController::postes');
    $routes->get('postes/nouveau', 'Departement\AccueilController::nouveauPoste');
    $routes->post('postes', 'Departement\AccueilController::creerPoste');
    $routes->post('postes/(:num)/statut', 'Departement\AccueilController::changerStatutPoste/$1');
    $routes->get('postes/(:num)/conditions', 'Departement\AccueilController::conditions/$1');
    $routes->post('postes/(:num)/conditions', 'Departement\AccueilController::enregistrerConditions/$1');
    $routes->get('demandes', 'Departement\AccueilController::demandes');
    $routes->get('demandes/nouveau', 'Departement\AccueilController::nouvelleDemande');
    $routes->post('demandes', 'Departement\AccueilController::creerDemande');
    $routes->get('demandes/(:num)', 'Departement\AccueilController::detailDemande/$1');
});

// -------------------------------------------------------------------------
// Espace RH — Tsiky
// -------------------------------------------------------------------------

$routes->group('rh', ['filter' => 'role:RH,ADMINISTRATEUR'], static function ($routes) {
    $routes->get('/', 'Rh\AccueilController::index');

    // Conditions de contrat
    $routes->get('conditions', 'Rh\ConditionsController::index');
    $routes->get('conditions/(:num)', 'Rh\ConditionsController::show/$1');

    $routes->get('conditions/(:num)/create', 'Rh\ConditionsController::create/$1');
    $routes->post('conditions/(:num)/store', 'Rh\ConditionsController::store/$1');

    $routes->get('conditions/edit/(:num)', 'Rh\ConditionsController::edit/$1');
    $routes->post('conditions/(:num)/update', 'Rh\ConditionsController::update/$1');
    $routes->post('conditions/(:num)/delete', 'Rh\ConditionsController::delete/$1');

    $routes->post('conditions/(:num)/confirm', 'Rh\ConditionsController::confirm/$1');

    $routes->get('demandes', 'Rh\DemandesController::index');
    $routes->get('demandes/(:num)', 'Rh\DemandesController::show/$1');

    $routes->post('demandes/decision', 'Rh\DemandesController::decision');
    $routes->post('demandes/reouvrir', 'Rh\DemandesController::reouvrir');
    // Offres d'emploi
    $routes->get('offres', 'Rh\OffresController::index');
    $routes->get('offres/demandes', 'Rh\OffresController::demandesEligibles');
    $routes->get('offres/creer/(:num)', 'Rh\OffresController::creer/$1');
    $routes->post('offres/enregistrer', 'Rh\OffresController::enregistrer');
    $routes->get('offres/(:num)', 'Rh\OffresController::show/$1');
    $routes->post('offres/soumettre/(:num)', 'Rh\OffresController::soumettre/$1');
    $routes->post('offres/publier/(:num)', 'Rh\OffresController::publier/$1');
    $routes->post('offres/fermer/(:num)', 'Rh\OffresController::fermer/$1');

    // Candidatures RH
    $routes->get(
        'offres/(:num)/candidatures',
        'Rh\CandidaturesController::index/$1'
    );

    $routes->get(
        'candidatures/(:num)',
        'Rh\CandidaturesController::detail/$1'
    );

    $routes->get(
        'candidatures/(:num)/document/(:segment)',
        'Rh\CandidaturesController::document/$1/$2'
    );

    $routes->post(
        'candidatures/(:num)/decider',
        'Rh\CandidaturesController::decider/$1'
    );

    $routes->get(
        'candidatures/(:num)/entretien',
        'Rh\CandidaturesController::entretien/$1'
    );

    // Idealy.5 — Entretiens
    $routes->get('entretiens', 'Rh\Entretiens::index');
    $routes->get('entretiens/nouveau/(:num)', 'Rh\Entretiens::nouveau/$1');   // après présélection
    $routes->post('entretiens/creer', 'Rh\Entretiens::creer');
    $routes->post('entretiens/(:num)/statut', 'Rh\Entretiens::statut/$1');

    // Idealy.5 — Tests techniques
    $routes->get('tests', 'Rh\TestsTechniques::index');
    $routes->get('tests/nouveau/(:num)', 'Rh\TestsTechniques::nouveau/$1');
    $routes->post('tests/creer', 'Rh\TestsTechniques::creer');
    $routes->post('tests/(:num)/statut', 'Rh\TestsTechniques::statut/$1');

    // Idealy.6 — Évaluations
    $routes->get('evaluations', 'Rh\Evaluations::index');
    $routes->get('evaluations/(:num)', 'Rh\Evaluations::candidature/$1');
    $routes->post('evaluations/(:num)/evaluer', 'Rh\Evaluations::evaluer/$1');
    $routes->post('evaluations/(:num)/decider', 'Rh\Evaluations::decider/$1');

    // Idealy.6 — Requêtes du candidat
    $routes->get('requetes', 'Rh\Requetes::index');
    $routes->get('requetes/(:num)', 'Rh\Requetes::candidature/$1');
    $routes->post('requetes/(:num)/decision', 'Rh\Requetes::decision/$1');

    // Dossiers administratifs des salariés
    $routes->get('employes', 'Rh\EmployesController::index');
    $routes->get('employes/(:num)', 'Rh\EmployesController::show/$1');
    $routes->get('employes/(:num)/documents/(:num)', 'Rh\EmployesController::document/$1/$2');

    // Bloc 7 — congés et absences.
    $routes->get('conges', 'Rh\CongesController::index');
    $routes->post('conges', 'Rh\CongesController::store');
    $routes->post('conges/(:num)/decision', 'Rh\CongesController::decision/$1');
    $routes->post('absences', 'Rh\CongesController::absence');
    $routes->get('temps-travail', 'Rh\TempsTravailController::index');
    $routes->post('temps-travail/pointage', 'Rh\TempsTravailController::pointer');
    $routes->post('employes/(:num)/salaires', 'Rh\EmployesController::ajouterSalaire/$1');
    $routes->post('employes/(:num)/affectations', 'Rh\EmployesController::ajouterAffectation/$1');
    $routes->post('employes/(:num)/documents', 'Rh\EmployesController::ajouterDocument/$1');
    // ------------------------------------------------------------------
    // Blocs 8 à 12 (squelette complet, posé une seule fois)
    // ------------------------------------------------------------------
    // Paie : consultation, état Excel, bulletin PDF
    $routes->get('modules-avances', 'Rh\ModulesAvancesController::index');
    $routes->get('modules-avances/paie/etat/(:num)', 'Rh\ModulesAvancesController::etatPaie/$1');
    $routes->get('modules-avances/paie/bulletin/(:num)', 'Rh\ModulesAvancesController::bulletinPdf/$1');
    $routes->get('modules-avances/paie/consulter', 'Rh\ModulesAvancesController::consulterPaie');

    // Embauche directe, contrats, statut employé
    $routes->get('employes/nouveau', 'Rh\EmbaucheController::nouveau');
    $routes->post('employes/creer', 'Rh\EmbaucheController::creer');
    $routes->get('employes/(:num)/parcours', 'Rh\CarriereController::parcours/$1');
    $routes->get('contrats/(:num)/pdf', 'Rh\ContratsController::pdf/$1');
    $routes->post('contrats/(:num)/statut', 'Rh\ContratsController::changerStatut/$1');

    // Paie
    $routes->get('paie/periodes', 'Rh\PeriodesPaieController::index');
    $routes->post('paie/periodes', 'Rh\PeriodesPaieController::creer');
    $routes->post('paie/periodes/(:num)/statut', 'Rh\PeriodesPaieController::statut/$1');
    $routes->get('paie/variables', 'Rh\VariablesPaieController::index');
    $routes->post('paie/variables', 'Rh\VariablesPaieController::enregistrer');
    $routes->post('paie/variables/(:num)/supprimer', 'Rh\VariablesPaieController::supprimer/$1');
    $routes->get('paie/heures-supplementaires', 'Rh\HeuresSuppController::index');
    $routes->get('paie/heures-supplementaires/apercu', 'Rh\HeuresSuppController::apercu');
    $routes->post('paie/heures-supplementaires', 'Rh\HeuresSuppController::enregistrer');
    $routes->post('paie/heures-supplementaires/(:num)/decision', 'Rh\HeuresSuppController::decision/$1');
    $routes->get('temps-travail/retards', 'Rh\TempsTravailController::retards');
    $routes->post('temps-travail/retards', 'Rh\TempsTravailController::creerRetard');
    $routes->post('temps-travail/retards/(:num)/justifier', 'Rh\TempsTravailController::justifier/$1');
    $routes->post('temps-travail/retards/(:num)/valider', 'Rh\TempsTravailController::validerRetard/$1');

    // Paramètres RH
    $routes->get('parametres', 'Rh\ParametresRhController::index');
    $routes->post('parametres', 'Rh\ParametresRhController::enregistrer');

    // Évaluation et formation
    $routes->get('competences', 'Rh\CompetencesController::index');
    $routes->post('competences', 'Rh\CompetencesController::enregistrer');
    $routes->post('competences/poste', 'Rh\CompetencesController::lierPoste');
    $routes->get('performance', 'Rh\PerformanceController::index');
    $routes->get('performance/nouvelle/(:num)', 'Rh\PerformanceController::nouvelle/$1');
    $routes->post('performance/enregistrer', 'Rh\PerformanceController::enregistrer');
    $routes->post('performance/(:num)/valider', 'Rh\PerformanceController::valider/$1');
    $routes->get('performance/(:num)', 'Rh\PerformanceController::detail/$1');
    $routes->get('formations', 'Rh\FormationsController::index');
    $routes->post('formations', 'Rh\FormationsController::creer');
    $routes->get('formations/propositions', 'Rh\FormationsController::propositions');
    $routes->post('formations/propositions/(:num)/assigner', 'Rh\FormationsController::assignerProposition/$1');
    $routes->post('formations/propositions/(:num)/refuser', 'Rh\FormationsController::refuserProposition/$1');
    $routes->post('formations/propositions/recalculer', 'Rh\FormationsController::recalculer');
    $routes->get('formations/assigner', 'Rh\FormationsController::assigner');
    $routes->post('formations/assigner', 'Rh\FormationsController::enregistrerAssignation');
    $routes->get('formations/apport', 'Rh\FormationsController::apport');
    $routes->get('formations/(:num)', 'Rh\FormationsController::fiche/$1');
    $routes->post('formations/(:num)/statut', 'Rh\FormationsController::statut/$1');
    $routes->post('formations/(:num)/niveau-apres', 'Rh\FormationsController::niveauApres/$1');
    $routes->post('formations/(:num)/participants/(:num)/statut', 'Rh\FormationsController::statutParticipant/$1/$2');

    // Carrière
    $routes->get('carriere', 'Rh\CarriereController::index');
    $routes->get('carriere/mouvement', 'Rh\CarriereController::formulaire');
    $routes->post('carriere/mouvement', 'Rh\CarriereController::enregistrer');
    $routes->post('carriere/(:num)/annuler', 'Rh\CarriereController::annuler/$1');
    $routes->get('carriere/propositions', 'Rh\CarriereController::propositions');
    $routes->post('carriere/propositions/generer', 'Rh\CarriereController::generer');
    $routes->post('carriere/propositions/(:num)/suivre', 'Rh\CarriereController::suivre/$1');
    $routes->post('carriere/propositions/(:num)/refuser', 'Rh\CarriereController::refuser/$1');

    // Discipline
    $routes->get('discipline/incidents', 'Rh\DisciplineController::incidents');
    $routes->get('discipline/incidents/(:num)', 'Rh\DisciplineController::dossier/$1');
    $routes->post('discipline/incidents', 'Rh\DisciplineController::creerIncident');
    $routes->post('discipline/incidents/(:num)/explications', 'Rh\DisciplineController::demanderExplication/$1');
    $routes->post('discipline/incidents/(:num)/reponse', 'Rh\DisciplineController::saisirReponse/$1');
    $routes->post('discipline/incidents/(:num)/commission', 'Rh\DisciplineController::commission/$1');
    $routes->post('discipline/incidents/(:num)/decision', 'Rh\DisciplineController::decider/$1');
    $routes->get('discipline/sanctions', 'Rh\DisciplineController::sanctions');
    $routes->post('discipline/sanctions/(:num)/statut', 'Rh\DisciplineController::statutSanction/$1');
    $routes->get('discipline/reponses', 'Rh\DisciplineController::reponses');
    $routes->get('discipline/reponses/(:num)/piece', 'Rh\DisciplineController::piece/$1');

    // Ruptures
    $routes->get('ruptures', 'Rh\RupturesController::index');
    $routes->post('ruptures', 'Rh\RupturesController::creer');
    $routes->get('ruptures/(:num)', 'Rh\RupturesController::show/$1');
    $routes->post('ruptures/(:num)/statut', 'Rh\RupturesController::statut/$1');
    $routes->post('ruptures/(:num)/checklist/(:num)', 'Rh\RupturesController::checklist/$1/$2');
    $routes->post('ruptures/(:num)/stc/calculer', 'Rh\RupturesController::calculerStc/$1');
    $routes->post('ruptures/(:num)/stc/statut', 'Rh\RupturesController::statutStc/$1');
    $routes->get('ruptures/(:num)/stc/pdf', 'Rh\RupturesController::stcPdf/$1');
    $routes->get('ruptures/(:num)/attestations/(:num)/pdf', 'Rh\RupturesController::attestationPdf/$1/$2');
});

// -------------------------------------------------------------------------
// Espace Direction — D / Rindra
// -------------------------------------------------------------------------
$routes->group('direction', ['filter' => 'role:DIRECTION,FINANCE'], static function ($routes) {
    $routes->get('/', 'Direction\AccueilController::index');

    // Offres (D.1.1)
    $routes->get('offres',                'Direction\OffresController::index');
    $routes->get('offres/(:num)',         'Direction\OffresController::show/$1');
    $routes->post('offres/(:num)/valider','Direction\OffresController::valider/$1');
    $routes->post('offres/(:num)/refuser','Direction\OffresController::refuser/$1');

    // Candidatures (D.1.2)
    $routes->get('candidatures',                            'Direction\CandidaturesController::index');
    $routes->get('candidatures/(:num)',                     'Direction\CandidaturesController::show/$1');
    $routes->post('candidatures/(:num)/retenir',            'Direction\CandidaturesController::retenir/$1');
    $routes->get('candidatures/(:num)/document/(:segment)', 'Direction\CandidaturesController::document/$1/$2');


    // Embauche directe et validation des ruptures (contrôleurs RH partagés)
    $routes->get('employes/nouveau', 'Rh\EmbaucheController::nouveau');
    $routes->post('employes/creer', 'Rh\EmbaucheController::creer');
    $routes->get('ruptures/(:num)', 'Rh\RupturesController::show/$1');
    $routes->post('ruptures/(:num)/statut', 'Rh\RupturesController::statut/$1');

    // Demandes de recrutement
    $routes->get('demandes',                 'Direction\DemandesController::index');
    $routes->get('demandes/(:num)',          'Direction\DemandesController::show/$1');
    $routes->post('demandes/(:num)/valider', 'Direction\DemandesController::valider/$1');
    $routes->post('demandes/(:num)/refuser', 'Direction\DemandesController::refuser/$1');
});

$routes->group('api', ['filter' => 'role:CANDIDAT'], static function ($routes) {
    $routes->get('candidat/dashboard', 'Api\CandidatApiController::dashboard');
    $routes->get('notifications',                       'Api\CandidatApiController::notifications');
    $routes->post('notifications/(:num)/marquer-lue',   'Api\CandidatApiController::marquerLue/$1');
    $routes->post('notifications/tout-marquer-lu',      'Api\CandidatApiController::toutMarquerLu');
    $routes->get('candidat/offres',                        'Api\CandidatApiController::offres');
    $routes->get('candidat/mes-candidatures',              'Api\CandidatApiController::mesCandidatures');   // ⬅️
    $routes->get('candidat/requetes',                      'Api\CandidatApiController::requetes');          // ⬅️
    $routes->get('candidat/contrats',                      'Api\CandidatApiController::contrats');          // ⬅️
});

// -------------------------------------------------------------------------
// Espace Candidat — D / Rindra
// -------------------------------------------------------------------------
$routes->group('candidat', ['filter' => 'role:CANDIDAT'], static function ($routes) {
    // D.2.1 — Accueil + liste offres publiées
    $routes->get('/',         'Candidat\AccueilController::index');
    $routes->get('offres',    'Candidat\OffresController::index');

    // D.2.2 — Postuler (décommenter à l'étape suivante)
    $routes->get('offres/(:num)/postuler',   'Candidat\CandidaturesController::formulaire/$1');
    $routes->post('offres/(:num)/postuler',  'Candidat\CandidaturesController::postuler/$1');

    $routes->get('mes-candidatures',        'Candidat\CandidaturesController::mesCandidatures');

     // D.2.3 — Notifications
    $routes->get('notifications',                       'Candidat\NotificationsController::index');
    $routes->get('notifications/(:num)',                'Candidat\NotificationsController::show/$1');
    $routes->post('notifications/(:num)/marquer-lue',   'Candidat\NotificationsController::marquerLue/$1');
    $routes->post('notifications/tout-marquer-lu',      'Candidat\NotificationsController::toutMarquerLu');

     // D.3 — Requêtes sur conditions
    $routes->get('requetes',                             'Candidat\RequetesController::index');
    $routes->get('requetes/(:num)',                      'Candidat\RequetesController::show/$1');
    $routes->get('requetes/(:num)/creer',                'Candidat\RequetesController::formulaire/$1');
    $routes->post('requetes/(:num)/creer',               'Candidat\RequetesController::soumettre/$1');

        // D.4 — Contrat
    $routes->get('contrats',                          'Candidat\ContratController::index');
    $routes->get('contrats/(:num)',                   'Candidat\ContratController::show/$1');
    $routes->post('contrats/(:num)/accepter',         'Candidat\ContratController::accepter/$1');
    $routes->post('contrats/(:num)/refuser',          'Candidat\ContratController::refuser/$1');
    $routes->get('contrats/(:num)/pdf',               'Candidat\ContratController::telechargerPdf/$1');
// D.4.2 — Signature
$routes->post('contrats/(:num)/signer', 'Candidat\ContratController::signer/$1');

});

// -------------------------------------------------------------------------
// API JSON — pour les composants Vue
// -------------------------------------------------------------------------
$routes->group('api', ['filter' => 'role:DIRECTION,FINANCE'], static function ($routes) {
    $routes->get('direction/dashboard', 'Api\DirectionApiController::dashboard');
    $routes->get('direction/candidatures', 'Api\DirectionApiController::candidatures');
    $routes->get('direction/offres',       'Api\DirectionApiController::offres');
});

// -------------------------------------------------------------------------
// Espace Employé
// -------------------------------------------------------------------------
$routes->group('employe', ['filter' => 'role:EMPLOYE,ADMINISTRATEUR'], static function ($routes) {
    $routes->get('/', 'Employe\AccueilController::index');
    $routes->get('notifications', 'Employe\NotificationsController::index');
    $routes->post('notifications/(:num)/marquer-lue', 'Employe\NotificationsController::marquerLue/$1');
    $routes->post('notifications/tout-marquer-lu', 'Employe\NotificationsController::toutMarquerLu');
    $routes->get('formations', 'Employe\FormationsController::index');
    $routes->get('discipline', 'Employe\DisciplineController::index');
    $routes->post('discipline/(:num)/reponse', 'Employe\DisciplineController::repondre/$1');
    $routes->post('discipline/sanctions/(:num)/contester', 'Employe\DisciplineController::contester/$1');
    $routes->get('bulletins', 'Employe\BulletinsController::index');
    $routes->get('bulletins/(:num)/pdf', 'Employe\BulletinsController::pdf/$1');
});

// -------------------------------------------------------------------------
// Admin (technique) — Danie / commun
// -------------------------------------------------------------------------
$routes->group('admin', ['filter' => 'role:ADMINISTRATEUR'], static function ($routes) {
    $routes->get('/', 'Admin\AccueilController::index');
});

// -------------------------------------------------------------------------
// Pages authentifiées partagées (tous rôles connectés)
// -------------------------------------------------------------------------
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    // Ex. profil, notifications génériques — à compléter
});

// -----------------------------------------------------------------------------
$routes->get('dev/login-as/(:segment)', static function ($role) {
    // Comptes du seed (DemoDatabaseSeeder). EMPLOYE : employe3 = Faly Randria (incident en cours) ;
    // EMPLOYE1 = Lova Rabe (formations, évaluations) ; EMPLOYE2 = Soa Rakotoarisoa (rupture FIN_CDD).
    $map = ['DIRECTION'      => 'direction@test.local',
            'CANDIDAT'       => 'candidat@test.local',
            'CANDIDAT2'      => 'candidat2@test.local',
            'CANDIDAT3'      => 'candidat3@test.local',
            'CANDIDAT4'      => 'candidat4@test.local',
            'RH'             => 'rh@test.local',
            'DEPARTEMENT'    => 'departement@test.local',
            'ADMINISTRATEUR' => 'admin@test.local',
            'FINANCE'        => 'finance@test.local',
            'EMPLOYE'        => 'employe3@test.local',
            'EMPLOYE1'       => 'employe1@test.local',
            'EMPLOYE2'       => 'employe2@test.local'];
    if (! isset($map[$role])) {
        return 'Rôle inconnu';
    }
    $u = \Config\Database::connect()->table('utilisateur')->where('email', $map[$role])->get()->getRowArray();
    if (! $u) {
        return 'Seed manquant : lancez php spark db:reset --force';
    }
    session()->set(['id_utilisateur' => $u['id_utilisateur'], 'email' => $u['email'], 'role' => $u['role']]);
    return redirect()->to(match ($u['role']) {
        'DIRECTION' => '/direction', 'CANDIDAT' => '/candidat', 'RH' => '/rh', 'DEPARTEMENT' => '/departement',
        'ADMINISTRATEUR' => '/admin', 'FINANCE' => '/direction', 'EMPLOYE' => '/employe', default => '/',
    });
});
$routes->get('dev/logout', static function () {
    session()->destroy();
    return redirect()->to('/');
});