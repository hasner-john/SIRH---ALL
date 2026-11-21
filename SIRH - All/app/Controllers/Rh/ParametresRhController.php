<?php

declare(strict_types=1);

namespace App\Controllers\Rh;

use App\Libraries\Audit;
use App\Libraries\ParametreRh;

class ParametresRhController extends RhController
{
    public function index()
    {
        $rows = $this->db->table('parametre_rh')->orderBy('groupe')->orderBy('code')->get()->getResultArray();
        $groupes = [];
        foreach ($rows as $r) {
            $groupes[$r['groupe']][] = $r;
        }
        return view('rh/parametres', ['titre' => 'Paramètres RH', 'groupes' => $groupes]);
    }

    /** Enregistre un paramètre (champs : code, valeur, actif) avec audit ancienne/nouvelle valeur. */
    public function enregistrer()
    {
        $code = (string) $this->request->getPost('code');
        $valeur = $this->request->getPost('valeur');
        $row = $this->db->table('parametre_rh')->where('code', $code)->get()->getRowArray();
        if (! $row || ! is_numeric($valeur)) {
            return $this->ko('Paramètre ou valeur invalide.');
        }
        $actif = (int) (bool) $this->request->getPost('actif');
        $this->db->table('parametre_rh')->where('code', $code)->update([
            'valeur' => (float) $valeur, 'actif' => $actif, 'modifie_par' => $this->uid(), 'date_modification' => gmdate('Y-m-d H:i:s'),
        ]);
        Audit::log('UPDATE', 'parametre_rh', (int) $row['id_parametre_rh'], ['valeur' => $row['valeur'], 'actif' => $row['actif']], ['valeur' => (float) $valeur, 'actif' => $actif], $code);
        ParametreRh::vider();
        return $this->ok('Paramètre enregistré.');
    }
}
