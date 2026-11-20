<?php

namespace App\Controllers\Departement;

use App\Controllers\BaseController;
use App\Models\ConditionContratModel;
use App\Models\DemandeRecrutementModel;
use App\Models\DepartementModel;
use App\Models\PosteModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class AccueilController extends BaseController
{
    private DepartementModel $departements;
    private PosteModel $postes;
    private ConditionContratModel $conditions;
    private DemandeRecrutementModel $demandes;

    public function __construct()
    {
        $this->departements = new DepartementModel();
        $this->postes = new PosteModel();
        $this->conditions = new ConditionContratModel();
        $this->demandes = new DemandeRecrutementModel();
    }

    public function index()
    {
        $userId = $this->userId();
        $status = trim((string) $this->request->getGet('statut'));

        return view('departement/accueil', [
            'titre' => 'Espace Département',
            'role' => session()->get('role'),
            'departements' => $this->departements->forUser($userId),
            'postes' => $this->postes->forUser($userId),
            'demandes' => $this->demandes->forUser($userId, $status),
            'statusFiltre' => $status,
        ]);
    }

    public function postes()
    {
        $userId = $this->userId();

        return view('departement/postes', [
            'titre' => 'Gestion des postes',
            'postes' => $this->postes->forUser(
                $userId,
                $this->intGet('departement')
            ),
            'departements' => $this->departements->forUser($userId),
            'selectedDepartment' => $this->intGet('departement'),
        ]);
    }

    public function nouveauPoste()
    {
        return view('departement/poste_form', [
            'titre' => 'Ajouter un poste',
            'departements' => $this->departements->forUser($this->userId()),
            'validation' => service('validation'),
        ]);
    }

    public function creerPoste()
    {
        $userId = $this->userId();
        $rules = [
            'id_departement' => 'required|is_natural_no_zero',
            'libelle' => 'required|min_length[2]|max_length[150]',
            'description' => 'permit_empty|max_length[5000]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $departmentId = (int) $this->request->getPost('id_departement');
        if (! $this->departements->belongsToUser($departmentId, $userId)) {
            return redirect()->back()->withInput()->with('error', 'Département non autorisé.');
        }

        $db = db_connect();
        $db->transStart();

        $postId = $this->postes->insert([
            'id_departement' => $departmentId,
            'libelle' => trim((string) $this->request->getPost('libelle')),
            'description' => trim((string) $this->request->getPost('description')),
            'statut' => 'ACTIF',
            'cree_par' => $userId,
        ], true);

        if ($postId === false) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', 'Impossible de créer le poste.');
        }

        db_connect()->table('historique_poste')->insert([
            'id_poste' => $postId,
            'ancien_statut' => null,
            'nouveau_statut' => 'ACTIF',
            'id_utilisateur' => $userId,
            'commentaire' => 'Création du poste.',
        ]);

        $db->transComplete();
        if ($db->transStatus() === false) {
            return redirect()->back()->withInput()->with('error', 'La création du poste a échoué.');
        }

        return redirect()->to(site_url('departement/postes/' . $postId . '/conditions'))
            ->with('success', 'Poste créé. Ajoutez maintenant ses conditions.');
    }

    public function conditions(int $postId)
    {
        $poste = $this->postes->findAuthorized($postId, $this->userId());
        if ($poste === null) {
            throw PageNotFoundException::forPageNotFound('Poste introuvable.');
        }

        return view('departement/conditions', [
            'titre' => 'Conditions du poste',
            'poste' => $poste,
            'conditions' => $this->conditions->forPoste($postId),
            'typesValeur' => ['TEXTE', 'NOMBRE', 'MONTANT', 'DATE', 'BOOLEEN'],
        ]);
    }

    public function changerStatutPoste(int $postId)
    {
        $poste = $this->postes->findAuthorized($postId, $this->userId());
        if ($poste === null) {
            throw PageNotFoundException::forPageNotFound('Poste introuvable.');
        }

        $statut = (string) $this->request->getPost('statut');
        if (! in_array($statut, ['ACTIF', 'INACTIF', 'FERME'], true)) {
            return redirect()->back()->with('error', 'Statut de poste invalide.');
        }

        if ($statut === $poste['statut']) {
            return redirect()->back()->with('error', 'Le poste possède déjà ce statut.');
        }

        $db = db_connect();
        $db->transStart();
        $this->postes->update($postId, ['statut' => $statut]);
        $db->table('historique_poste')->insert([
            'id_poste' => $postId,
            'ancien_statut' => $poste['statut'],
            'nouveau_statut' => $statut,
            'id_utilisateur' => $this->userId(),
            'commentaire' => 'Modification du statut du poste.',
        ]);
        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->with('error', 'Le statut du poste n’a pas pu être modifié.');
        }

        return redirect()->back()->with('success', 'Statut du poste mis à jour.');
    }

    public function enregistrerConditions(int $postId)
    {
        $poste = $this->postes->findAuthorized($postId, $this->userId());
        if ($poste === null) {
            throw PageNotFoundException::forPageNotFound('Poste introuvable.');
        }

        $libelles = $this->request->getPost('libelle');
        $types = $this->request->getPost('type_valeur');
        $valeurs = $this->request->getPost('valeur_condition');
        $ordres = $this->request->getPost('ordre_affichage');

        if (! is_array($libelles) || ! is_array($types) || ! is_array($valeurs)) {
            return redirect()->back()->with('error', 'Ajoutez au moins une condition.');
        }

        $validTypes = ['TEXTE', 'NOMBRE', 'MONTANT', 'DATE', 'BOOLEEN'];
        $rows = [];
        foreach ($libelles as $index => $libelle) {
            $label = trim((string) $libelle);
            $type = (string) ($types[$index] ?? '');
            $value = trim((string) ($valeurs[$index] ?? ''));
            $order = (int) ($ordres[$index] ?? ($index + 1));

            if ($label === '' || ! in_array($type, $validTypes, true) || $value === '' || $order < 1) {
                return redirect()->back()->withInput()
                    ->with('error', 'Chaque condition doit avoir un libellé, un type, une valeur et un ordre valide.');
            }

            $rows[] = [
                'id_poste' => $postId,
                'libelle' => $label,
                'type_valeur' => $type,
                'valeur_condition' => $value,
                'ordre_affichage' => $order,
                'statut' => 'CREER',
                'modifie_par' => $this->userId(),
            ];
        }

        $db = db_connect();
        $db->transStart();
        foreach ($rows as $row) {
            $conditionId = $this->conditions->insert($row, true);
            if ($conditionId === false) {
                $db->transRollback();
                return redirect()->back()->withInput()->with('error', 'Impossible d’enregistrer les conditions.');
            }

            $db->table('historique_condition_contrat')->insert([
                'id_condition' => $conditionId,
                'ancienne_valeur' => null,
                'nouvelle_valeur' => $row['valeur_condition'],
                'ancien_statut' => null,
                'nouveau_statut' => 'CREER',
                'id_utilisateur' => $this->userId(),
                'commentaire' => 'Création de la condition.',
            ]);
        }
        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->withInput()->with('error', 'L’enregistrement des conditions a échoué.');
        }

        return redirect()->to(site_url('departement/postes/' . $postId . '/conditions'))
            ->with('success', 'Conditions enregistrées.');
    }

    public function demandes()
    {
        $status = trim((string) $this->request->getGet('statut'));

        return view('departement/demandes', [
            'titre' => 'Demandes de recrutement',
            'demandes' => $this->demandes->forUser($this->userId(), $status),
            'statusFiltre' => $status,
        ]);
    }

    public function nouvelleDemande()
    {
        $departmentId = $this->intGet('departement');

        return view('departement/demande_form', [
            'titre' => 'Nouvelle demande de recrutement',
            'departements' => $this->departements->forUser($this->userId()),
            'postes' => $this->postes->forUser($this->userId(), $departmentId),
            'selectedDepartment' => $departmentId,
            'selectedPost' => $this->intGet('poste'),
        ]);
    }

    public function creerDemande()
    {
        $rules = [
            'id_poste' => 'required|is_natural_no_zero',
            'effectif_demande' => 'required|is_natural_no_zero',
            'motif' => 'required|max_length[5000]',
            'salaire_par_personne' => 'required|decimal',
            'date_besoin' => 'required|valid_date[Y-m-d]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $postId = (int) $this->request->getPost('id_poste');
        if ($this->postes->findAuthorized($postId, $this->userId()) === null) {
            return redirect()->back()->withInput()->with('error', 'Poste non autorisé.');
        }

        $salary = (float) $this->request->getPost('salaire_par_personne');
        if ($salary < 0) {
            return redirect()->back()->withInput()->with('error', 'Le salaire ne peut pas être négatif.');
        }

        $db = db_connect();
        $db->transStart();
        $demandId = $this->demandes->insert([
            'id_poste' => $postId,
            'id_demandeur' => $this->userId(),
            'effectif_demande' => (int) $this->request->getPost('effectif_demande'),
            'motif' => trim((string) $this->request->getPost('motif')),
            'salaire_par_personne' => $salary,
            'date_besoin' => $this->request->getPost('date_besoin'),
            'statut' => 'BROUILLON',
        ], true);

        if ($demandId === false) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', 'Impossible de créer la demande.');
        }

        $db->table('historique_demande')->insert([
            'id_demande' => $demandId,
            'ancien_statut' => null,
            'nouveau_statut' => 'BROUILLON',
            'id_utilisateur' => $this->userId(),
            'commentaire' => 'Création de la demande.',
        ]);
        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->withInput()->with('error', 'La demande n’a pas pu être enregistrée.');
        }

        return redirect()->to(site_url('departement/demandes/' . $demandId))
            ->with('success', 'Demande enregistrée en brouillon.');
    }

    public function detailDemande(int $demandId)
    {
        $demande = $this->demandes->findAuthorized($demandId, $this->userId());
        if ($demande === null) {
            throw PageNotFoundException::forPageNotFound('Demande introuvable.');
        }

        return view('departement/demande_detail', [
            'titre' => 'Détail de la demande',
            'demande' => $demande,
            'historique' => db_connect()->table('historique_demande')
                ->where('id_demande', $demandId)
                ->orderBy('date_action', 'DESC')
                ->get()->getResultArray(),
        ]);
    }

    private function userId(): int
    {
        return (int) session()->get('id_utilisateur');
    }

    private function intGet(string $name): ?int
    {
        $value = $this->request->getGet($name);
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}
