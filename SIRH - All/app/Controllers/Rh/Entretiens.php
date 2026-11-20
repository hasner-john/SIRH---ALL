<?php
namespace App\Controllers\Rh;

use App\Controllers\BaseController;
use App\Models\CandidatureModel;
use App\Models\EntretienModel;
use DomainException;

class Entretiens extends BaseController
{
    private function uid(): int
    {
        return (int) session()->get('id_utilisateur'); // jamais depuis le formulaire (RG-HIST-005)
    }

    public function index()
    {
        return view('rh/entretiens/index', ['entretiens' => (new EntretienModel())->liste()]);
    }

    public function nouveau(int $idCand)
    {
        $cand = (new CandidatureModel())->infos($idCand);
        if (! $cand || $cand['statut'] !== 'PRESELECTIONNEE') {
            return redirect()->to('/rh/evaluations')->with('erreur', 'Candidature non présélectionnée.');
        }
        return view('rh/entretiens/form', ['cand' => $cand]);
    }

    public function creer()
    {
        $rules = [
            'id_candidature' => 'required|is_natural_no_zero',
            'date_entretien' => 'required|valid_date[Y-m-d\TH:i]',
            'lieu'           => 'permit_empty|max_length[255]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('erreur', implode(' ', $this->validator->getErrors()));
        }
        try {
            (new EntretienModel())->planifier(
                (int) $this->request->getPost('id_candidature'),
                $this->uid(),
                str_replace('T', ' ', $this->request->getPost('date_entretien')),
                $this->request->getPost('lieu') ?: null,
                $this->request->getPost('notes') ?: null
            );
        } catch (DomainException $e) {
            return redirect()->back()->withInput()->with('erreur', $e->getMessage());
        }
        return redirect()->to('/rh/entretiens')->with('succes', 'Entretien planifié.');
    }

    public function statut(int $id)
    {
        try {
            (new EntretienModel())->changerStatut(
                $id, (string) $this->request->getPost('statut'), $this->uid(),
                $this->request->getPost('commentaire') ?: null
            );
        } catch (DomainException $e) {
            return redirect()->back()->with('erreur', $e->getMessage());
        }
        return redirect()->back()->with('succes', 'Statut mis à jour.');
    }
}
