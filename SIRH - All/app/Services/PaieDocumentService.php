<?php

declare(strict_types=1);

namespace App\Services;

use Config\Database;
use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PaieDocumentService
{
    public function etatPaieXlsx(int $periodeId): array
    {
        $db = Database::connect();
        $p = $db->table('periode_paie')->where('id_periode', $periodeId)->get()->getRowArray();
        if (! $p) {
            throw new \RuntimeException('Période introuvable.');
        }
        $fin = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $p['annee'], $p['mois'])));
        $rows = $db->query(
            "SELECT b.*, e.matricule, e.numero_cnaps, e.categorie, e.date_embauche, pe.nom, pe.prenom,
              (SELECT a.fonction FROM affectation a WHERE a.id_employe = e.id_employe
                 AND a.date_debut <= ? AND (a.date_fin IS NULL OR a.date_fin >= ?)
                 ORDER BY a.date_debut DESC LIMIT 1) AS fonction
             FROM bulletin_paie b
             JOIN employe e ON e.id_employe = b.id_employe
             JOIN personne pe ON pe.id_personne = e.id_personne
             WHERE b.id_periode = ? ORDER BY e.matricule",
            [$fin, $fin, $periodeId]
        )->getResultArray();

        $ss = new Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->setTitle('État de paie');
        $ws->setCellValue('A1', sprintf('ÉTAT DE PAIE — %02d/%04d', $p['mois'], $p['annee']));
        $heads = ['DATE', 'NBRE', 'N° MATR', 'N° CNaPS', 'NOM et Prénoms', "Date d'embauche", 'ABSENCE du mois (h)',
            'CAT', 'Fonction', 'SALAIRE DE BASE', 'RETENUES SUR ABSENCE', 'SALAIRE DE BASE DU MOIS', 'INDEMNITÉS',
            'RAPPEL AUTRES', 'HR SUP / MAJORATIONS', 'TOTAL SALAIRE BRUT', 'CNaPS SALARIAL (1%)',
            'CNaPS PATRONAL (13%)', 'OSTIE SALARIAL (1%)', 'OSTIE PATRONAL (5%)', 'NET IMPOSABLE', 'IRSA',
            'TOTAL RETENUES', 'NET À PAYER'];
        $ws->fromArray($heads, null, 'A4');

        $date = strtolower(date('d-M-y', strtotime($p['date_paiement'] ?? 'now')));
        $r = 5;
        $i = 1;
        foreach ($rows as $b) {
            $ws->fromArray([
                $date, $i++, $b['matricule'], $b['numero_cnaps'], $b['nom'] . ' ' . $b['prenom'], $b['date_embauche'],
                (float) $b['heures_absence'], $b['categorie'], $b['fonction'],
                (float) $b['salaire_base'], (float) $b['retenue_absence'], (float) $b['base_mois'],
                (float) $b['indemnites'], (float) $b['rappels'], (float) $b['majorations'], (float) $b['salaire_brut'],
                (float) $b['cnaps_salarial'], (float) $b['cnaps_patronal'], (float) $b['ostie_salarial'],
                (float) $b['ostie_patronal'], (float) $b['net_imposable'], (float) $b['irsa'],
                (float) $b['total_retenues'], (float) $b['net_a_payer'],
            ], null, 'A' . $r++);
        }
        $last = max(5, $r - 1);
        $ws->setCellValue("A$r", 'TOTAL GÉNÉRAL');
        foreach (range('J', 'X') as $col) {
            $ws->setCellValue("$col$r", "=SUM({$col}5:{$col}{$last})");
        }
        $ws->getStyle("J5:X$r")->getNumberFormat()->setFormatCode('#,##0');
        $ws->getStyle('A4:X4')->getFont()->setBold(true);
        $ws->getStyle("A$r:X$r")->getFont()->setBold(true);
        foreach (range('A', 'X') as $col) {
            $ws->getColumnDimension($col)->setAutoSize(true);
        }

        ob_start();
        (new Xlsx($ss))->save('php://output');
        return [sprintf('etat_paie_%04d_%02d.xlsx', $p['annee'], $p['mois']), (string) ob_get_clean()];
    }

    public function bulletinPdf(int $idBulletin): array
    {
        $db = Database::connect();
        $b = $db->query(
            "SELECT b.*, e.matricule, e.numero_cnaps, e.categorie, e.secteur, e.date_embauche, e.id_employe AS eid,
                    pe.nom, pe.prenom, p.mois, p.annee, p.date_paiement
             FROM bulletin_paie b
             JOIN employe e ON e.id_employe = b.id_employe
             JOIN personne pe ON pe.id_personne = e.id_personne
             JOIN periode_paie p ON p.id_periode = b.id_periode
             WHERE b.id_bulletin = ?", [$idBulletin]
        )->getRowArray();
        if (! $b) {
            throw new \RuntimeException('Bulletin introuvable.');
        }
        $fin = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $b['annee'], $b['mois'])));
        $lignes = $db->table('ligne_bulletin')->where('id_bulletin', $idBulletin)->orderBy('ordre_affichage')->get()->getResultArray();
        $fonction = $db->query(
            "SELECT fonction FROM affectation WHERE id_employe = ? AND date_debut <= ?
             AND (date_fin IS NULL OR date_fin >= ?) ORDER BY date_debut DESC LIMIT 1", [$b['eid'], $fin, $fin]
        )->getRow('fonction');
        $cumuls = $db->query(
            "SELECT SUM(b.salaire_brut) AS brut, SUM(b.irsa) AS irsa FROM bulletin_paie b
             JOIN periode_paie p ON p.id_periode = b.id_periode
             WHERE b.id_employe = ? AND p.annee = ? AND p.mois <= ?", [$b['eid'], $b['annee'], $b['mois']]
        )->getRowArray();
        $conges = $db->query(
            "SELECT s.acquis, s.pris, s.ajuste FROM solde_conge s JOIN type_conge t ON t.id_type_conge = s.id_type_conge
             WHERE s.id_employe = ? AND s.annee = ? AND t.code = 'ANNUEL'", [$b['eid'], $b['annee']]
        )->getRowArray() ?: ['acquis' => 0, 'pris' => 0, 'ajuste' => 0];

        $svc = new PaieService();
        $html = view('rh/paie/bulletin_pdf', [
            'b' => $b, 'lignes' => $lignes, 'fonction' => $fonction, 'cumuls' => $cumuls, 'conges' => $conges,
            'tranches' => $svc->detailIrsa((float) $b['net_imposable'], $fin),
            'entreprise' => config('Entreprise'),
            'lettres' => $this->enLettres((float) $b['net_a_payer']),
            'taux_jour' => $b['salaire_base'] / 30,
            'anciennete' => (new \DateTime($b['date_embauche']))->diff(new \DateTime($fin))->y . ' an(s)',
        ]);
        $pdf = new Dompdf();
        $pdf->loadHtml($html);
        $pdf->setPaper('A4');
        $pdf->render();
        $bin = $pdf->output();
        $nom = sprintf('bulletin_%s_%04d_%02d.pdf', $b['matricule'], $b['annee'], $b['mois']);
        $db->table('bulletin_paie')->where('id_bulletin', $idBulletin)
            ->update(['pdf_nom' => $nom, 'pdf_type_mime' => 'application/pdf', 'pdf_donnees' => $bin]);
        return [$nom, $bin, (int) $b['eid']];
    }

    private function enLettres(float $n): string
    {
        if (! class_exists(\NumberFormatter::class)) {
            return number_format($n, 0, ',', ' ') . ' Ariary';
        }
        $f = new \NumberFormatter('fr', \NumberFormatter::SPELLOUT);
        return ucfirst($f->format((int) round($n))) . ' Ariary';
    }
}