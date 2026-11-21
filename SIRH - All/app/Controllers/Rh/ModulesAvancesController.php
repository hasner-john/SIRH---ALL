<?php

declare(strict_types=1);

namespace App\Controllers\Rh;

use App\Controllers\BaseController;
use App\Services\PaieService;
use App\Services\PaieDocumentService;

class ModulesAvancesController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        return view('rh/modules_avances/index', [
            'titre' => 'Paie et modules avancés',
            'periodes' => $db->table('periode_paie')->orderBy('annee', 'DESC')->orderBy('mois', 'DESC')->get()->getResultArray(),
            'bulletins' => $db->table('bulletin_paie bp')
                ->select('bp.*, e.matricule, pe.nom, pe.prenom, p.mois, p.annee')
                ->join('employe e', 'e.id_employe = bp.id_employe')
                ->join('personne pe', 'pe.id_personne = e.id_personne')
                ->join('periode_paie p', 'p.id_periode = bp.id_periode')
                ->orderBy('bp.id_bulletin', 'DESC')->get()->getResultArray(),
        ]);
    }

    public function etatPaie(int $idPeriode)
    {
        try {
            [$nom, $bin] = (new PaieDocumentService())->etatPaieXlsx($idPeriode);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $nom . '"')
            ->setBody($bin);
    }

    public function bulletinPdf(int $idBulletin)
    {
        try {
            [$nom, $bin, $idEmploye] = (new PaieDocumentService())->bulletinPdf($idBulletin);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
        // RG-TRANS-04 : log d'accès
        \Config\Database::connect()->table('journal_acces')->insert([
            'id_utilisateur' => (int) session()->get('id_utilisateur'),
            'id_employe' => $idEmploye, 'action' => 'TELECHARGEMENT', 'ressource' => 'bulletin_paie',
            'adresse_ip' => $this->request->getIPAddress(), 'details' => 'Bulletin #' . $idBulletin,
        ]);
        return $this->response->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $nom . '"')->setBody($bin);
    }

    public function consulterPaie()
    {
        $date = (string) $this->request->getGet('date');
        $ts = strtotime($date);
        if ($date === '' || ! $ts) {
            return redirect()->to(site_url('rh/modules-avances'))->with('error', 'Date invalide.');
        }
        $db = \Config\Database::connect();

        // 1) date de paiement exacte, 2) sinon période du mois contenant la date
        $periode = $db->table('periode_paie')->where('date_paiement', date('Y-m-d', $ts))->get()->getRowArray()
            ?: $db->table('periode_paie')->where('mois', (int) date('n', $ts))
                ->where('annee', (int) date('Y', $ts))->get()->getRowArray();

        $bulletins = [];
        if ($periode) {
            $bulletins = $db->table('bulletin_paie bp')
                ->select('bp.*, e.matricule, pe.nom, pe.prenom')
                ->join('employe e', 'e.id_employe = bp.id_employe')
                ->join('personne pe', 'pe.id_personne = e.id_personne')
                ->where('bp.id_periode', $periode['id_periode'])
                ->orderBy('e.matricule')->get()->getResultArray();

            // RG-TRANS-04 : log de consultation
            $db->table('journal_acces')->insert([
                'id_utilisateur' => (int) session()->get('id_utilisateur'),
                'action' => 'CONSULTATION', 'ressource' => 'etat_paie',
                'adresse_ip' => $this->request->getIPAddress(),
                'details' => 'Période #' . $periode['id_periode'] . ' via date ' . $date,
            ]);
        }
        return view('rh/paie/consultation', ['titre' => 'État de paie', 'date' => $date,
            'periode' => $periode, 'bulletins' => $bulletins]);
    }
}
