<?php

declare(strict_types=1);

namespace App\Controllers\Employe;

class FormationsController extends EmployeBaseController
{
    public function index()
    {
        $rows = $this->db->table('formation_participant fp')->select('f.titre, f.date_debut, f.date_fin, f.prestataire, f.statut AS statut_formation, fp.statut, fp.niveau_avant, fp.niveau_apres')
            ->join('formation f', 'f.id_formation = fp.id_formation')->where('fp.id_employe', (int) ($this->emp['id_employe'] ?? 0))->orderBy('f.date_debut', 'DESC')->get()->getResultArray();
        return $this->vue('employe/formations', ['titre' => 'Mes formations', 'formations' => $rows]);
    }
}
