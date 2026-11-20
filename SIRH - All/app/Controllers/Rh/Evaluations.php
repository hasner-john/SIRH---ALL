<?php
namespace App\Controllers\Rh;

use App\Controllers\BaseController;
use App\Models\CandidatureModel;
use App\Models\EntretienModel;
use App\Models\EvaluationModel;
use App\Models\TestTechniqueModel;
use DomainException;

class Evaluations extends BaseController
{
    private function uid(): int
    {
        return (int) session()->get('id_utilisateur');
    }

    public function index()
    {
        return view('rh/evaluations/index', ['candidatures' => (new CandidatureModel())->preselectionnees()]);
    }

    public function candidature(int $id)
    {
        $cand = (new CandidatureModel())->infos($id);
        if (! $cand) {
            return redirect()->to('/rh/evaluations')->with('erreur', 'Candidature introuvable.');
        }
        $evalModel = new EvaluationModel();
        $map = [];
        foreach ($evalModel->deCandidature($id) as $e) {
            $map[$e['type_evaluation'] . '-' . ($e['id_entretien'] ?? $e['id_test'] ?? 0)] = $e;
        }
        return view('rh/evaluations/candidature', [
            'cand'       => $cand,
            'entretiens' => (new EntretienModel())->deCandidature($id),
            'tests'      => (new TestTechniqueModel())->deCandidature($id),
            'evals'      => $map,
            'historique' => $evalModel->historique($id),
        ]);
    }

    public function evaluer(int $idCand)
    {
        $rules = [
            'type_evaluation' => 'required|in_list[DOSSIER,ENTRETIEN,TEST_TECHNIQUE]',
            'note'            => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[10]',
            'ref'             => 'permit_empty|is_natural_no_zero',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('erreur', implode(' ', $this->validator->getErrors()));
        }
        $note = $this->request->getPost('note');
        $ref  = $this->request->getPost('ref');
        try {
            (new EvaluationModel())->evaluer(
                $idCand,
                (string) $this->request->getPost('type_evaluation'),
                $ref !== null && $ref !== '' ? (int) $ref : null,
                $note !== null && $note !== '' ? (float) $note : null,
                $this->request->getPost('commentaire') ?: null,
                $this->uid()
            );
        } catch (DomainException $e) {
            return redirect()->back()->withInput()->with('erreur', $e->getMessage());
        }
        return redirect()->back()->with('succes', 'Évaluation enregistrée.');
    }

    public function decider(int $idCand)
    {
        try {
            (new CandidatureModel())->decider(
                $idCand, (string) $this->request->getPost('decision'), $this->uid(),
                $this->request->getPost('commentaire') ?: null
            );
        } catch (DomainException $e) {
            return redirect()->back()->with('erreur', $e->getMessage());
        }
        return redirect()->to('/rh/evaluations/' . $idCand)->with('succes', 'Décision enregistrée.');
    }
}
