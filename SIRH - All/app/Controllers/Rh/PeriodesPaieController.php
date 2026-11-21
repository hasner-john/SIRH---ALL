<?php

declare(strict_types=1);

namespace App\Controllers\Rh;

use App\Libraries\Audit;
use App\Libraries\Transitions;
use App\Services\PaieService;
use App\Services\PeriodePaieService;

class PeriodesPaieController extends RhController
{
    public function index()
    {
        $periodes = $this->db->query(
            "SELECT p.*, (SELECT COUNT(*) FROM bulletin_paie b WHERE b.id_periode = p.id_periode) AS nb_bulletins,
                    (SELECT COUNT(*) FROM bulletin_paie b WHERE b.id_periode = p.id_periode AND b.statut IN ('PAIE')) AS nb_payes
             FROM periode_paie p ORDER BY p.annee DESC, p.mois DESC"
        )->getResultArray();
        return view('rh/paie/periodes', ['titre' => 'Périodes de paie', 'periodes' => $periodes]);
    }

    public function creer()
    {
        $mois = (int) $this->request->getPost('mois');
        $annee = (int) $this->request->getPost('annee');
        if ($mois < 1 || $mois > 12 || $annee < 2000) {
            return $this->ko('Mois ou année invalide.');
        }
        if ($this->db->table('periode_paie')->where(['mois' => $mois, 'annee' => $annee])->countAllResults() > 0) {
            return $this->ko('Cette période existe déjà.');
        }
        $date = (string) $this->request->getPost('date_paiement');
        if (! $this->validDate($date)) {
            $date = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $annee, $mois)));
        }
        $this->db->table('periode_paie')->insert(['mois' => $mois, 'annee' => $annee, 'date_paiement' => $date, 'statut' => 'OUVERT']);
        Audit::log('CREATE', 'periode_paie', (int) $this->db->insertID(), null, ['mois' => $mois, 'annee' => $annee]);
        return $this->ok('Période ouverte.');
    }

    /** Actions : calculer, valider, payer, cloturer, annuler (toutes via Transitions). */
    public function statut(int $id)
    {
        $action = (string) $this->request->getPost('action');
        $p = $this->db->table('periode_paie')->where('id_periode', $id)->get()->getRowArray();
        if (! $p) {
            return $this->ko('Période introuvable.');
        }
        try {
            switch ($action) {
                case 'calculer':
                    $n = (new PaieService())->calculerPeriode($id, $this->uid(), $this->request->getIPAddress());
                    if ($p['statut'] === 'OUVERT') {
                        [$ok, $msg] = Transitions::appliquer('periode_paie', $id, 'CALCUL', 'Calcul des bulletins');
                        if (! $ok) {
                            return $this->ko($msg);
                        }
                    }
                    return $this->ok("$n bulletin(s) calculé(s).");

                case 'valider':
                    return $this->changer($id, 'VALIDE', 'BROUILLON', 'VALIDE', 'Période validée : bulletins verrouillés.');

                case 'payer':
                    return $this->changer($id, 'PAIE', 'VALIDE', 'PAIE', 'Période marquée payée.');

                case 'cloturer':
                    $reste = $this->db->table('bulletin_paie')->where('id_periode', $id)->whereNotIn('statut', ['PAIE', 'ANNULE'])->countAllResults();
                    if ($reste > 0) {
                        return $this->ko('Clôture impossible : tous les bulletins doivent être payés.');
                    }
                    [$ok, $msg] = Transitions::appliquer('periode_paie', $id, 'CLOTURE');
                    return $ok ? $this->ok('Période clôturée.') : $this->ko($msg);

                case 'annuler':
                    [$ok, $msg] = Transitions::appliquer('periode_paie', $id, 'ANNULE');
                    return $ok ? $this->ok('Période annulée.') : $this->ko($msg);
            }
        } catch (\Throwable $e) {
            return $this->ko($e->getMessage());
        }
        return $this->ko('Action inconnue.');
    }

    /** Transition de période + passage en bloc des bulletins, dans une transaction. */
    private function changer(int $id, string $vers, string $bulletinDe, string $bulletinVers, string $msgOk)
    {
        $this->db->transBegin();
        [$ok, $msg] = Transitions::appliquer('periode_paie', $id, $vers);
        if (! $ok) {
            $this->db->transRollback();
            return $this->ko($msg);
        }
        $this->db->table('bulletin_paie')->where('id_periode', $id)->where('statut', $bulletinDe)->update(['statut' => $bulletinVers]);
        if (! $this->db->transStatus()) {
            $this->db->transRollback();
            return $this->ko('Opération impossible.');
        }
        $this->db->transCommit();
        return $this->ok($msgOk);
    }
}
