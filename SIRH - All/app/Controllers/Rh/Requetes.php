<?php
namespace App\Controllers\Rh;

use App\Controllers\BaseController;
use App\Models\CandidatureModel;
use App\Models\RequeteConditionModel;
use DomainException;

class Requetes extends BaseController
{
    public function index()
    {
        return view('rh/requetes/index', ['candidats' => (new RequeteConditionModel())->candidatsAvecRequetes()]);
    }

    public function candidature(int $idCand)
    {
        $cand = (new CandidatureModel())->infos($idCand);
        if (! $cand) {
            return redirect()->to('/rh/requetes')->with('erreur', 'Candidature introuvable.');
        }
        return view('rh/requetes/candidature', [
            'cand'     => $cand,
            'requetes' => (new RequeteConditionModel())->deCandidature($idCand),
        ]);
    }

    public function decision(int $idRequete)
    {
        $d = $this->request->getPost('decision');
        if (! in_array($d, ['accepter', 'refuser'], true)) {
            return redirect()->back()->with('erreur', 'Décision invalide.');
        }
        try {
            $idCand = (new RequeteConditionModel())->decisionRh(
                $idRequete, $d === 'accepter', (int) session()->get('id_utilisateur'),
                $this->request->getPost('commentaire') ?: null
            );
        } catch (DomainException $e) {
            return redirect()->back()->with('erreur', $e->getMessage());
        }
        return redirect()->to('/rh/requetes/' . $idCand)->with('succes', 'Décision RH enregistrée.');
    }
}
