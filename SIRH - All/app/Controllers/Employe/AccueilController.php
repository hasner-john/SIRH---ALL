<?php

declare(strict_types=1);

namespace App\Controllers\Employe;

class AccueilController extends EmployeBaseController
{
    public function index()
    {
        $idE = (int) ($this->emp['id_employe'] ?? 0);
        return $this->vue('employe/accueil', [
            'titre' => 'Mon espace',
            'nonLues' => $this->db->table('notification')->where(['id_utilisateur' => $this->uid(), 'lue' => 0])->countAllResults(),
            'formations' => $this->db->table('formation_participant fp')->select('f.titre, f.date_debut, f.date_fin, fp.statut')->join('formation f', 'f.id_formation = fp.id_formation')
                ->where('fp.id_employe', $idE)->whereIn('fp.statut', ['INSCRIT', 'PRESENT'])->orderBy('f.date_debut')->get()->getResultArray(),
            'explications' => $this->db->query(
                "SELECT i.id_incident, i.objet, d.date_limite FROM incident i JOIN demande_explication d ON d.id_incident = i.id_incident
                 WHERE i.id_employe = ? AND i.statut = 'EXPLICATIONS'", [$idE]
            )->getResultArray(),
        ]);
    }
}
