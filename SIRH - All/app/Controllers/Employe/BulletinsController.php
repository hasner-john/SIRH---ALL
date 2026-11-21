<?php

declare(strict_types=1);

namespace App\Controllers\Employe;

use App\Libraries\Audit;
use App\Services\PaieDocumentService;

class BulletinsController extends EmployeBaseController
{
    public function index()
    {
        $idE = (int) ($this->emp['id_employe'] ?? 0);
        $bulletins = $this->db->table('bulletin_paie b')->select('b.id_bulletin, b.net_a_payer, b.statut, p.mois, p.annee')->join('periode_paie p', 'p.id_periode = b.id_periode')
            ->where('b.id_employe', $idE)->whereIn('b.statut', ['VALIDE', 'PAIE'])->orderBy('p.annee', 'DESC')->orderBy('p.mois', 'DESC')->get()->getResultArray();
        return $this->vue('employe/bulletins', ['titre' => 'Mes bulletins', 'bulletins' => $bulletins]);
    }

    public function pdf(int $id)
    {
        $b = $this->db->table('bulletin_paie')->where(['id_bulletin' => $id, 'id_employe' => (int) ($this->emp['id_employe'] ?? 0)])->whereIn('statut', ['VALIDE', 'PAIE'])->get()->getRowArray();
        if (! $b) {
            return redirect()->to(site_url('employe/bulletins'))->with('error', 'Bulletin introuvable.');
        }
        [$nom, $bin, $idE] = (new PaieDocumentService())->bulletinPdf($id);
        Audit::acces('TELECHARGEMENT', 'bulletin_paie', $idE, 'Bulletin #' . $id . ' (employé)');
        return $this->response->setHeader('Content-Type', 'application/pdf')->setHeader('Content-Disposition', 'inline; filename="' . $nom . '"')->setBody($bin);
    }
}
