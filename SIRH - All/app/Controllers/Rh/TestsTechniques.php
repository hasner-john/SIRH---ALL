<?php
namespace App\Controllers\Rh;

use App\Controllers\BaseController;
use App\Models\CandidatureModel;
use App\Models\TestTechniqueModel;
use DomainException;

class TestsTechniques extends BaseController
{
    private function uid(): int
    {
        return (int) session()->get('id_utilisateur');
    }

    public function index()
    {
        return view('rh/tests/index', ['tests' => (new TestTechniqueModel())->liste()]);
    }

    public function nouveau(int $idCand)
    {
        $cand = (new CandidatureModel())->infos($idCand);
        if (! $cand || $cand['statut'] !== 'PRESELECTIONNEE') {
            return redirect()->to('/rh/evaluations')->with('erreur', 'Candidature non présélectionnée.');
        }
        return view('rh/tests/form', ['cand' => $cand]);
    }

    public function creer()
    {
        $rules = [
            'id_candidature' => 'required|is_natural_no_zero',
            'intitule'       => 'required|max_length[180]',
            'date_test'      => 'required|valid_date[Y-m-d\TH:i]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('erreur', implode(' ', $this->validator->getErrors()));
        }
        try {
            (new TestTechniqueModel())->planifier(
                (int) $this->request->getPost('id_candidature'),
                $this->uid(),
                (string) $this->request->getPost('intitule'),
                $this->request->getPost('description') ?: null,
                str_replace('T', ' ', $this->request->getPost('date_test'))
            );
        } catch (DomainException $e) {
            return redirect()->back()->withInput()->with('erreur', $e->getMessage());
        }
        return redirect()->to('/rh/tests')->with('succes', 'Test technique planifié.');
    }

    public function statut(int $id)
    {
        try {
            (new TestTechniqueModel())->changerStatut(
                $id, (string) $this->request->getPost('statut'), $this->uid(),
                $this->request->getPost('commentaire') ?: null
            );
        } catch (DomainException $e) {
            return redirect()->back()->with('erreur', $e->getMessage());
        }
        return redirect()->back()->with('succes', 'Statut mis à jour.');
    }
}
