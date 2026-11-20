<?php
namespace App\Controllers\Rh;

use App\Controllers\BaseController;
use App\Models\PointageModel;
use App\Services\TempsTravailService;

class TempsTravailController extends BaseController
{
    public function index()
    {
        return view('rh/temps_travail/index', [
            'pointages' => (new PointageModel())->recent(),
        ]);
    }

    public function pointer()
    {
        $post = $this->request->getPost();
        foreach (['heure_entree', 'heure_sortie'] as $champ) {
            if (!empty($post[$champ])) {
                $post[$champ] = str_replace('T', ' ', $post[$champ]);
                if (strlen($post[$champ]) === 16) {
                    $post[$champ] .= ':00';
                }
            }
        }
        $rules = [
            'id_employe' => 'required|is_natural_no_zero',
            'date_pointage' => 'required|valid_date[Y-m-d]',
            'heure_entree' => 'permit_empty|valid_date[Y-m-d H:i:s]',
            'heure_sortie' => 'permit_empty|valid_date[Y-m-d H:i:s]',
        ];
        if (!$this->validateData($post, $rules)) {
            return redirect()->back()->withInput()->with('erreur', implode(' ', $this->validator->getErrors()));
        }
        try {
            (new TempsTravailService())->enregistrerPointage($post);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('erreur', $e->getMessage());
        }
        return redirect()->to(site_url('rh/temps-travail'))->with('succes', 'Pointage enregistré.');
    }
}
