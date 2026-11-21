<?php

declare(strict_types=1);

namespace App\Services;

use Config\Database;
use Dompdf\Dompdf;

class ContratPdfService
{
    /**
     * Génère (ou sert tel quel) le PDF du contrat. RG-TRANS-02 : un contrat SIGNE déjà généré n'est jamais régénéré.
     * Retourne [nom, mime, binaire].
     */
    public function generer(int $idContrat): array
    {
        $db = Database::connect();
        $c = $db->table('contrat')->where('id_contrat', $idContrat)->get()->getRowArray();
        if (! $c) {
            throw new \RuntimeException("Contrat #$idContrat introuvable.");
        }
        if ($c['statut'] === 'SIGNE' && ! empty($c['pdf_donnees'])) {
            return [$c['pdf_nom'], $c['pdf_type_mime'] ?: 'application/pdf', $c['pdf_donnees']];
        }

        // Salarié : via l'employé du contrat, sinon via la candidature
        $pers = $db->query(
            "SELECT pe.nom, pe.prenom, pe.adresse, pe.telephone, u.email
             FROM personne pe JOIN utilisateur u ON u.id_utilisateur = pe.id_utilisateur
             WHERE pe.id_personne = COALESCE(
                (SELECT e.id_personne FROM employe e WHERE e.id_contrat = ?),
                (SELECT cd.id_personne FROM candidature ca JOIN candidat cd ON cd.id_candidat = ca.id_candidat WHERE ca.id_candidature = ?))",
            [$idContrat, $c['id_candidature']]
        )->getRowArray() ?: [];
        $poste = $db->query(
            "SELECT p.libelle, d.nom AS departement FROM poste p JOIN departement d ON d.id_departement = p.id_departement WHERE p.id_poste = ?",
            [$c['id_poste']]
        )->getRowArray() ?: [];
        $conditions = $db->table('contrat_condition')->where('id_contrat', $idContrat)->orderBy('ordre_affichage')->get()->getResultArray();

        $html = view('rh/contrats/contrat_pdf', [
            'c' => $c, 'pers' => $pers, 'poste' => $poste, 'conditions' => $conditions, 'entreprise' => config('Entreprise'),
        ]);
        $pdf = new Dompdf(['isRemoteEnabled' => false]);
        $pdf->loadHtml($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();
        $bin = $pdf->output();
        $nom = 'contrat_' . $c['reference'] . '.pdf';
        $db->table('contrat')->where('id_contrat', $idContrat)->update(['pdf_nom' => $nom, 'pdf_type_mime' => 'application/pdf', 'pdf_donnees' => $bin]);
        return [$nom, 'application/pdf', $bin];
    }
}
