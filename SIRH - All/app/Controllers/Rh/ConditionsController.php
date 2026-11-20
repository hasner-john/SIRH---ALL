<?php

namespace App\Controllers\Rh;

use App\Controllers\BaseController;
use App\Models\ConditionContratModel;
use App\Models\HistoriqueConditionContratModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class ConditionsController extends BaseController
{
    private function utilisateurConnecte(): ?int
    {
        $id = session()->get('id_utilisateur');

        return $id !== null ? (int) $id : null;
    }

    public function index()
    {
        $model = new ConditionContratModel();

        return view('rh/conditions/index', [
            'titre' => 'Conditions de contrat',
            'postes' => $model->getPostesAvecNombreConditions(),
        ]);
    }

    public function show($idPoste)
    {
        $model = new ConditionContratModel();
        $poste = $model->getPoste((int) $idPoste);

        if (!$poste) {
            throw PageNotFoundException::forPageNotFound(
                'Poste introuvable.'
            );
        }

        return view('rh/conditions/show', [
            'poste' => $poste,
            'conditions' => $model->getConditionsParPoste((int) $idPoste),
        ]);
    }

    public function create($idPoste)
    {
        $model = new ConditionContratModel();
        $poste = $model->getPoste((int) $idPoste);

        if (!$poste) {
            throw PageNotFoundException::forPageNotFound(
                'Poste introuvable.'
            );
        }

        return view('rh/conditions/form', [
            'poste' => $poste,
            'condition' => null,
            'action' => site_url('rh/conditions/' . $idPoste . '/store'),
        ]);
    }

    public function store($idPoste)
    {
        $model = new ConditionContratModel();
        $poste = $model->getPoste((int) $idPoste);

        if (!$poste) {
            throw PageNotFoundException::forPageNotFound(
                'Poste introuvable.'
            );
        }

        $data = $this->validerDonnees();

        if ($data === null) {
            return redirect()->back()
                ->withInput()
                ->with('erreur', 'Vérifie les champs du formulaire.');
        }

        $data['id_poste'] = (int) $idPoste;
        $data['statut'] = 'CREER';
        $data['modifie_par'] = $this->utilisateurConnecte();

        $db = db_connect();
        $db->transBegin();

        $idCondition = $model->insert($data, true);

        if ($idCondition !== false) {
            $historique = new HistoriqueConditionContratModel();

            $historique->enregistrer(
                (int) $idCondition,
                null,
                $data['valeur_condition'],
                null,
                'CREER',
                $this->utilisateurConnecte(),
                'Création de la condition.'
            );
        }

        if ($idCondition === false || $db->transStatus() === false) {
            $db->transRollback();

            return redirect()->back()
                ->withInput()
                ->with('erreur', 'Impossible de créer la condition.');
        }

        $db->transCommit();

        return redirect()->to(site_url('rh/conditions/' . $idPoste))
            ->with('message', 'Condition ajoutée.');
    }

    public function edit($idCondition)
    {
        $model = new ConditionContratModel();
        $condition = $model->find((int) $idCondition);

        if (!$condition) {
            throw PageNotFoundException::forPageNotFound(
                'Condition introuvable.'
            );
        }

        if ($model->estDansUnContratSigne($condition)) {
            return redirect()->to(
                site_url('rh/conditions/' . $condition['id_poste'])
            )->with(
                'erreur',
                'Cette condition est intégrée à un contrat signé.'
            );
        }

        return view('rh/conditions/form', [
            'poste' => $model->getPoste((int) $condition['id_poste']),
            'condition' => $condition,
            'action' => site_url(
                'rh/conditions/' . $idCondition . '/update'
            ),
        ]);
    }

    public function update($idCondition)
    {
        $model = new ConditionContratModel();
        $condition = $model->find((int) $idCondition);

        if (!$condition) {
            throw PageNotFoundException::forPageNotFound(
                'Condition introuvable.'
            );
        }

        if ($model->estDansUnContratSigne($condition)) {
            return redirect()->to(
                site_url('rh/conditions/' . $condition['id_poste'])
            )->with(
                'erreur',
                'Cette condition est intégrée à un contrat signé.'
            );
        }

        $data = $this->validerDonnees();

        if ($data === null) {
            return redirect()->back()
                ->withInput()
                ->with('erreur', 'Vérifie les champs du formulaire.');
        }

        $data['modifie_par'] = $this->utilisateurConnecte();
        $data['date_modification'] = date('Y-m-d H:i:s');

        $db = db_connect();
        $db->transBegin();

        $ok = $model->update((int) $idCondition, $data);

        if ($ok) {
            $historique = new HistoriqueConditionContratModel();

            $historique->enregistrer(
                (int) $idCondition,
                $condition['valeur_condition'],
                $data['valeur_condition'],
                $condition['statut'],
                $condition['statut'],
                $this->utilisateurConnecte(),
                'Modification de la condition.'
            );
        }

        if (!$ok || $db->transStatus() === false) {
            $db->transRollback();

            return redirect()->back()
                ->withInput()
                ->with('erreur', 'Impossible de modifier la condition.');
        }

        $db->transCommit();

        return redirect()->to(
            site_url('rh/conditions/' . $condition['id_poste'])
        )->with('message', 'Condition modifiée.');
    }

    public function delete($idCondition)
    {
        $model = new ConditionContratModel();
        $condition = $model->find((int) $idCondition);

        if (!$condition) {
            throw PageNotFoundException::forPageNotFound(
                'Condition introuvable.'
            );
        }

        if ($model->estDansUnContratSigne($condition)) {
            return redirect()->to(
                site_url('rh/conditions/' . $condition['id_poste'])
            )->with(
                'erreur',
                'Cette condition ne peut pas être supprimée : elle est liée à un contrat signé.'
            );
        }

        if ($condition['statut'] === 'INACTIF') {
            return redirect()->to(
                site_url('rh/conditions/' . $condition['id_poste'])
            )->with('erreur', 'Cette condition est déjà inactive.');
        }

        $db = db_connect();
        $db->transBegin();

        $ok = $model->update((int) $idCondition, [
            'statut' => 'INACTIF',
            'modifie_par' => $this->utilisateurConnecte(),
            'date_modification' => date('Y-m-d H:i:s'),
        ]);

        if ($ok) {
            $historique = new HistoriqueConditionContratModel();

            $historique->enregistrer(
                (int) $idCondition,
                $condition['valeur_condition'],
                $condition['valeur_condition'],
                $condition['statut'],
                'INACTIF',
                $this->utilisateurConnecte(),
                'Désactivation de la condition.'
            );
        }

        if (!$ok || $db->transStatus() === false) {
            $db->transRollback();

            return redirect()->back()
                ->with('erreur', 'Impossible de désactiver la condition.');
        }

        $db->transCommit();

        return redirect()->to(
            site_url('rh/conditions/' . $condition['id_poste'])
        )->with('message', 'Condition désactivée.');
    }

    public function confirm($idPoste)
    {
        $model = new ConditionContratModel();
        $poste = $model->getPoste((int) $idPoste);

        if (!$poste) {
            throw PageNotFoundException::forPageNotFound(
                'Poste introuvable.'
            );
        }

        $conditions = $model->getConditionsParPoste((int) $idPoste);
        $aConfirmer = array_filter(
            $conditions,
            static fn($c) => in_array(
                $c['statut'],
                ['CREER', 'A_CONFIRMER'],
                true
            )
        );

        if (count($aConfirmer) === 0) {
            return redirect()->to(site_url('rh/conditions/' . $idPoste))
                ->with('erreur', 'Aucune condition à confirmer.');
        }

        $db = db_connect();
        $db->transBegin();
        $historique = new HistoriqueConditionContratModel();

        foreach ($aConfirmer as $condition) {
            $ok = $model->update((int) $condition['id_condition'], [
                'statut' => 'CONFIRMER',
                'modifie_par' => $this->utilisateurConnecte(),
                'date_modification' => date('Y-m-d H:i:s'),
            ]);

            if (!$ok) {
                $db->transRollback();

                return redirect()->to(
                    site_url('rh/conditions/' . $idPoste)
                )->with('erreur', 'Échec de la confirmation.');
            }

            $historique->enregistrer(
                (int) $condition['id_condition'],
                $condition['valeur_condition'],
                $condition['valeur_condition'],
                $condition['statut'],
                'CONFIRMER',
                $this->utilisateurConnecte(),
                'Confirmation des conditions du poste.'
            );
        }

        if ($db->transStatus() === false) {
            $db->transRollback();

            return redirect()->to(site_url('rh/conditions/' . $idPoste))
                ->with('erreur', 'Échec de la confirmation.');
        }

        $db->transCommit();

        return redirect()->to(site_url('rh/conditions/' . $idPoste))
            ->with('message', 'Conditions confirmées.');
    }

    private function validerDonnees(): ?array
    {
        $rules = [
            'libelle' => 'required|max_length[180]',
            'type_valeur' => 'required|in_list[TEXTE,NOMBRE,MONTANT,DATE,BOOLEEN]',
            'valeur_condition' => 'required',
            'ordre_affichage' => 'required|is_natural_no_zero',
        ];

        if (!$this->validate($rules)) {
            return null;
        }

        return [
            'libelle' => trim((string) $this->request->getPost('libelle')),
            'type_valeur' => $this->request->getPost('type_valeur'),
            'valeur_condition' => trim(
                (string) $this->request->getPost('valeur_condition')
            ),
            'ordre_affichage' => (int) $this->request->getPost('ordre_affichage'),
        ];
    }
}
