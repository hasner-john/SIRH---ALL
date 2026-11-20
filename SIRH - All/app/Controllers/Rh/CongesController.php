<?php
namespace App\Controllers\Rh;

use App\Controllers\BaseController;
use App\Models\DemandeCongeModel;
use App\Models\JustificatifCongeModel;
use App\Models\AbsenceModel;
use App\Models\TypeCongeModel;
use App\Services\CongesService;

class CongesController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $evenements = array_merge(
            $db->table('demande_conge dc')
                ->select("dc.date_debut, dc.date_fin, dc.statut, tc.libelle AS libelle,
                    e.matricule, p.prenom, p.nom, 'CONGE' AS type_evenement")
                ->join('type_conge tc', 'tc.id_type_conge = dc.id_type_conge')
                ->join('employe e', 'e.id_employe = dc.id_employe')
                ->join('personne p', 'p.id_personne = e.id_personne')
                ->whereIn('dc.statut', ['EN_ATTENTE', 'VALIDE'])
                ->get()->getResultArray(),
            $db->table('absence a')
                ->select("a.date_debut, a.date_fin, a.type_absence AS libelle,
                    a.justifiee, e.matricule, p.prenom, p.nom, 'ABSENCE' AS type_evenement")
                ->join('employe e', 'e.id_employe = a.id_employe')
                ->join('personne p', 'p.id_personne = e.id_personne')
                ->get()->getResultArray()
        );

        return view('rh/conges/index', [
            'demandes' => (new DemandeCongeModel())->avecDetails(),
            'types' => (new TypeCongeModel())->where('actif', 1)->findAll(),
            'evenements' => $evenements,
        ]);
    }

    public function store()
    {
        $data = $this->request->getPost([
            'id_employe', 'id_type_conge', 'date_debut', 'date_fin', 'nombre_jours', 'motif',
        ]);
        $id = (new CongesService())->creerDemande($data);
        if (!$id) {
            return redirect()->back()->withInput()->with('error', 'Solde insuffisant, dates invalides ou chevauchement.');
        }
        $fichier = $this->request->getFile('justificatif');
        if ($fichier && $fichier->isValid() && !$fichier->hasMoved()) {
            (new JustificatifCongeModel())->insert([
                'id_demande_conge' => $id,
                'nom_fichier' => $fichier->getClientName(),
                'type_mime' => $fichier->getClientMimeType(),
                'donnees' => $fichier->getTempName() ? file_get_contents($fichier->getTempName()) : '',
            ]);
        }
        return redirect()->to('rh/conges')->with('success', 'Demande enregistrée.');
    }

    public function decision(int $id)
    {
        $statut = $this->request->getPost('statut');
        $user = (int) (session()->get('id_utilisateur') ?? 0);
        if (!(new CongesService())->decider($id, $statut, $user ?: null, (string) $this->request->getPost('commentaire'))) {
            return redirect()->back()->with('error', 'Décision impossible.');
        }
        return redirect()->to('rh/conges')->with('success', 'Décision enregistrée.');
    }

    public function absence()
    {
        $data = $this->request->getPost([
            'id_employe', 'date_debut', 'date_fin', 'type_absence', 'motif', 'justifiee',
        ]);
        if (!$data['id_employe'] || !$data['date_debut'] || !$data['date_fin'] || $data['date_fin'] < $data['date_debut']) {
            return redirect()->back()->with('error', 'Dates d’absence invalides.');
        }
        $data['justifiee'] = !empty($data['justifiee']) ? 1 : 0;
        $data['cree_par'] = (int) (session()->get('id_utilisateur') ?? 0) ?: null;
        (new AbsenceModel())->insert($data);
        return redirect()->to('rh/conges')->with('success', 'Absence enregistrée.');
    }
}
