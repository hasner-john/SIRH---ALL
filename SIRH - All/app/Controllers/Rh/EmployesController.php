<?php

namespace App\Controllers\Rh;

use App\Controllers\BaseController;
use App\Models\EmployeModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class EmployesController extends BaseController
{
    public function index()
    {
        $employes = (new EmployeModel())->listeDossiers();

        return view('rh/employes/index', [
            'titre' => 'Dossiers administratifs',
            'employes' => $employes,
        ]);
    }

    public function show(int $id)
    {
        $model = new EmployeModel();
        $dossier = $model->dossier($id);

        if (! $dossier) {
            throw PageNotFoundException::forPageNotFound('Salarié introuvable.');
        }

        $this->journaliser($id, 'CONSULTATION', 'dossier_employe');

        return view('rh/employes/show', [
            'titre' => 'Dossier de ' . $dossier['prenom'] . ' ' . $dossier['nom'],
            'dossier' => $dossier,
            'affectations' => $model->affectations($id),
            'salaires' => $model->salaires($id),
            'documents' => $model->documents($id),
        ]);
    }

    public function document(int $idEmploye, int $idDocument)
    {
        $document = \Config\Database::connect()->table('document_employe')
            ->where(['id_document' => $idDocument, 'id_employe' => $idEmploye])
            ->get()->getRowArray();

        if (! $document) {
            throw PageNotFoundException::forPageNotFound('Document introuvable.');
        }

        $this->journaliser($idEmploye, 'TELECHARGEMENT', 'document_employe');

        return $this->response
            ->setHeader('Content-Type', $document['type_mime'])
            ->setHeader(
                'Content-Disposition',
                'attachment; filename="' . addslashes($document['nom_fichier']) . '"'
            )
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody($document['donnees']);
    }

    public function ajouterSalaire(int $idEmploye)
    {
        $this->assertEmployee($idEmploye);
        $salaire = (float) $this->request->getPost('nouveau_salaire');
        $dateEffet = (string) $this->request->getPost('date_effet');
        if ($salaire < 0 || ! $this->validDate($dateEffet)) {
            return redirect()->back()->withInput()->with('error', 'Salaire ou date invalide.');
        }

        $db = \Config\Database::connect();
        $ancien = $db->table('historique_salaire')->where('id_employe', $idEmploye)
            ->orderBy('date_effet', 'DESC')->get()->getRowArray();
        $db->transBegin();
        $db->table('historique_salaire')->insert([
            'id_employe' => $idEmploye,
            'ancien_salaire' => $ancien['nouveau_salaire'] ?? null,
            'nouveau_salaire' => $salaire,
            'periodicite_salaire' => (string) $this->request->getPost('periodicite_salaire'),
            'motif' => trim((string) $this->request->getPost('motif')),
            'id_utilisateur' => (int) session()->get('id_utilisateur'),
            'date_effet' => $dateEffet,
        ]);
        if (! $db->transStatus()) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Impossible d’enregistrer le salaire.');
        }
        $db->transCommit();
        $this->journaliser($idEmploye, 'CREATION', 'historique_salaire');
        return redirect()->back()->with('success', 'Salaire enregistré.');
    }

    public function ajouterAffectation(int $idEmploye)
    {
        $this->assertEmployee($idEmploye);
        $departement = (int) $this->request->getPost('id_departement');
        $dateDebut = (string) $this->request->getPost('date_debut');
        if ($departement <= 0 || ! $this->validDate($dateDebut) || trim((string) $this->request->getPost('fonction')) === '') {
            return redirect()->back()->withInput()->with('error', 'Affectation invalide.');
        }
        $db = \Config\Database::connect();
        $db->transBegin();
        $db->table('affectation')->where(['id_employe' => $idEmploye, 'date_fin' => null])
            ->update(['date_fin' => date('Y-m-d', strtotime($dateDebut . ' -1 day'))]);
        $db->table('affectation')->insert([
            'id_employe' => $idEmploye,
            'id_departement' => $departement,
            'date_debut' => $dateDebut,
            'fonction' => trim((string) $this->request->getPost('fonction')),
        ]);
        if (! $db->transStatus()) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Impossible d’enregistrer l’affectation.');
        }
        $db->transCommit();
        $this->journaliser($idEmploye, 'MODIFICATION', 'affectation');
        return redirect()->back()->with('success', 'Affectation enregistrée.');
    }

    public function ajouterDocument(int $idEmploye)
    {
        $this->assertEmployee($idEmploye);
        $file = $this->request->getFile('document');
        if (! $file || ! $file->isValid() || $file->getSize() > 5 * 1024 * 1024) {
            return redirect()->back()->with('error', 'Document absent, invalide ou trop volumineux.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getTempName());
        $allowed = ['application/pdf', 'image/jpeg', 'image/png'];
        if (! in_array($mime, $allowed, true)) {
            return redirect()->back()->with('error', 'Format de document non autorisé.');
        }
        \Config\Database::connect()->table('document_employe')->insert([
            'id_employe' => $idEmploye,
            'type_document' => trim((string) $this->request->getPost('type_document')),
            'nom_fichier' => $file->getClientName(),
            'type_mime' => $mime,
            'donnees' => file_get_contents($file->getTempName()),
            'date_expiration' => $this->request->getPost('date_expiration') ?: null,
            'commentaire' => trim((string) $this->request->getPost('commentaire')),
            'id_utilisateur' => (int) session()->get('id_utilisateur'),
        ]);
        $this->journaliser($idEmploye, 'CREATION', 'document_employe');
        return redirect()->back()->with('success', 'Document enregistré.');
    }

    private function assertEmployee(int $id): void
    {
        if (! (new EmployeModel())->find($id)) {
            throw PageNotFoundException::forPageNotFound('Salarié introuvable.');
        }
    }

    private function validDate(string $date): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }

    private function journaliser(int $idEmploye, string $action, string $ressource): void
    {
        \Config\Database::connect()->table('journal_acces')->insert([
            'id_utilisateur' => (int) session()->get('id_utilisateur'),
            'id_employe' => $idEmploye,
            'action' => $action,
            'ressource' => $ressource,
            'adresse_ip' => $this->request->getIPAddress(),
        ]);
    }
}
