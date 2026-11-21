<?php

declare(strict_types=1);

namespace App\Services;

use App\Libraries\Audit;
use Config\Database;
use Dompdf\Dompdf;

/** Certificat de travail et attestations CNaPS / OSTIE (PDF à la demande, stockés dans pdf_donnees). */
class AttestationService
{
    /** Retourne [nom, binaire]. */
    public function generer(int $idAttestation): array
    {
        $db = Database::connect();
        $a = $db->table('attestation_travail')->where('id_attestation', $idAttestation)->get()->getRowArray();
        if (! $a) {
            throw new \RuntimeException('Attestation introuvable.');
        }
        $r = $db->table('rupture_contrat')->where('id_rupture', $a['id_rupture'])->get()->getRowArray();
        $e = $db->table('employe e')->select('e.matricule, e.date_embauche, e.numero_cnaps, e.categorie, pe.nom, pe.prenom, pe.date_naissance')
            ->join('personne pe', 'pe.id_personne = e.id_personne')->where('e.id_employe', $r['id_employe'])->get()->getRowArray();
        $fonction = $db->table('affectation')->where('id_employe', $r['id_employe'])->orderBy('date_debut', 'DESC')->get(1)->getRow('fonction');
        $titres = ['CERTIFICAT_TRAVAIL' => 'CERTIFICAT DE TRAVAIL', 'ATTESTATION_CNAPS' => 'ATTESTATION CNaPS', 'ATTESTATION_OSTIE' => 'ATTESTATION OSTIE', 'AUTRE' => 'ATTESTATION'];
        $html = view('rh/ruptures/attestation_pdf', [
            'a' => $a, 'r' => $r, 'e' => $e, 'fonction' => $fonction, 'titre' => $titres[$a['type_attestation']] ?? 'ATTESTATION', 'entreprise' => config('Entreprise'),
        ]);
        $pdf = new Dompdf();
        $pdf->loadHtml($html);
        $pdf->setPaper('A4');
        $pdf->render();
        $bin = $pdf->output();
        $nom = strtolower($a['reference']) . '.pdf';
        $db->table('attestation_travail')->where('id_attestation', $idAttestation)->update(['pdf_nom' => $nom, 'pdf_type_mime' => 'application/pdf', 'pdf_donnees' => $bin, 'date_emission' => gmdate('Y-m-d H:i:s')]);
        if ($a['type_attestation'] === 'CERTIFICAT_TRAVAIL') {
            $db->table('checklist_offboarding')->where(['id_rupture' => $a['id_rupture'], 'element' => 'Certificat de travail'])
                ->update(['valeur_statut' => 'TERMINE', 'date_realisation' => gmdate('Y-m-d H:i:s')]);
        }
        Audit::log('CREATE', 'attestation_travail', $idAttestation, null, ['reference' => $a['reference']], 'Génération PDF');
        return [$nom, $bin];
    }
}
