<?php

declare(strict_types=1);

namespace App\Services;

use App\Libraries\Audit;
use App\Libraries\ParametrePaie;
use App\Libraries\ParametreRh;
use App\Libraries\Transitions;
use Config\Database;
use Dompdf\Dompdf;

/** Solde de tout compte : calcul (tant que BROUILLON), modification, validation, PDF. */
class StcService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /** Calcule le STC d'une rupture. Les montants manuels (retenues, indemnité manuelle) sont conservés. */
    public function calculer(int $idRupture): array
    {
        $r = $this->db->table('rupture_contrat')->where('id_rupture', $idRupture)->get()->getRowArray();
        $stc = $this->db->table('solde_tout_compte')->where('id_rupture', $idRupture)->get()->getRowArray();
        if (! $r) {
            throw new \RuntimeException('Rupture introuvable.');
        }
        if ($stc && $stc['statut'] !== 'BROUILLON') {
            throw new \RuntimeException('Le STC n’est plus modifiable (statut ' . $stc['statut'] . ').');
        }
        $e = $this->db->table('employe e')->select('e.*, c.salaire, c.date_debut')->join('contrat c', 'c.id_contrat = e.id_contrat')->where('e.id_employe', $r['id_employe'])->get()->getRowArray();
        $sect = $e['secteur'] ?: 'NON_AGRICOLE';
        $sortie = $r['date_sortie'];
        $dj = ParametrePaie::get('DIVISEUR_JOURS', $sect, $sortie, 30);

        $sal = $this->db->table('historique_salaire')->where('id_employe', $e['id_employe'])->where('date_effet <=', $sortie)->orderBy('date_effet', 'DESC')->orderBy('id_historique', 'DESC')->get(1)->getRowArray();
        $base = $sal ? (float) $sal['nouveau_salaire'] : (float) $e['salaire'];

        // Salaire proratisé : net du dernier bulletin (celui calculé AVEC la rupture : inclus_stc = 1),
        // sinon base × jours / 30. Un bulletin calculé avant la rupture (mois complet) est ignoré.
        $debutMois = date('Y-m-01', strtotime($sortie));
        $bul = $this->db->query(
            'SELECT b.* FROM bulletin_paie b JOIN periode_paie p ON p.id_periode = b.id_periode
             WHERE b.id_employe = ? AND p.mois = ? AND p.annee = ? AND b.statut != ? AND b.inclus_stc = 1',
            [$e['id_employe'], (int) date('n', strtotime($sortie)), (int) date('Y', strtotime($sortie)), 'ANNULE']
        )->getRowArray();
        if ($bul) {
            $proratise = (float) $bul['net_a_payer'];
        } else {
            $debutTrav = max($debutMois, $e['date_embauche']);
            $jours = min($dj, (strtotime($sortie) - strtotime($debutTrav)) / 86400 + 1);
            if ($sortie >= date('Y-m-t', strtotime($sortie)) && $debutTrav === $debutMois) {
                $jours = $dj;
            }
            $proratise = round($base * $jours / $dj, 2);
        }

        // Congés restants (ANNUEL, année de sortie)
        $c = $this->db->query(
            "SELECT s.acquis, s.pris, s.ajuste FROM solde_conge s JOIN type_conge t ON t.id_type_conge = s.id_type_conge WHERE s.id_employe = ? AND s.annee = ? AND t.code = 'ANNUEL'",
            [$e['id_employe'], (int) date('Y', strtotime($sortie))]
        )->getRowArray();
        $joursConges = $c ? max(0, (float) $c['acquis'] + (float) $c['ajuste'] - (float) $c['pris']) : 0.0;
        $indConges = round($joursConges * $base / $dj, 2);

        // Indemnité de rupture selon le motif
        $anc = max(0, (strtotime($sortie) - strtotime($e['date_embauche'])) / 86400 / 365.25);
        $indRupture = (float) ($stc['indemnite_rupture'] ?? 0);
        switch ($r['motif']) {
            case 'DEMISSION':
            case 'FIN_CDD':
            case 'LICENCIEMENT_FAUTE':
                $indRupture = 0.0;
                break;
            case 'LICENCIEMENT_ECONOMIQUE':
                $indRupture = round($anc * (float) ParametreRh::get('INDEM_LICENCIEMENT_ECO_JOURS_AN', 0) * $base / $dj, 2);
                break;
            case 'RETRAITE':
                $indRupture = round($anc * (float) ParametreRh::get('INDEM_RETRAITE_JOURS_AN', 0) * $base / $dj, 2);
                break;
            default:   // RUPTURE_CONVENTIONNELLE, AUTRE : montant saisi à la main (conservé)
        }

        // Avances non encore remboursées (variables AVANCE sans bulletin correspondant)
        $avances = (float) ($this->db->query(
            "SELECT COALESCE(SUM(v.montant),0) m FROM variable_paie v JOIN rubrique_paie ru ON ru.id_rubrique = v.id_rubrique
             WHERE v.id_employe = ? AND ru.categorie = 'AVANCE'
               AND NOT EXISTS (SELECT 1 FROM bulletin_paie b WHERE b.id_employe = v.id_employe AND b.id_periode = v.id_periode)",
            [$e['id_employe']]
        )->getRow('m'));
        $retenues = (float) ($stc['retenues'] ?? 0);
        $net = round($proratise + $indConges + $indRupture - $avances - $retenues, 2);

        $data = [
            'dernier_salaire' => $base, 'salaire_proratise' => $proratise, 'jours_conges_restants' => $joursConges, 'indemnite_conges' => $indConges,
            'indemnite_rupture' => $indRupture, 'avances' => $avances, 'retenues' => $retenues, 'montant_net' => $net,
            'id_bulletin_final' => $bul['id_bulletin'] ?? null, 'date_calcul' => gmdate('Y-m-d H:i:s'),
        ];
        if ($stc) {
            $this->db->table('solde_tout_compte')->where('id_stc', $stc['id_stc'])->update($data);
            $id = (int) $stc['id_stc'];
        } else {
            $this->db->table('solde_tout_compte')->insert(['id_rupture' => $idRupture, 'statut' => 'BROUILLON'] + $data);
            $id = (int) $this->db->insertID();
        }
        if ($bul) {
            $this->db->table('checklist_offboarding')->where(['id_rupture' => $idRupture, 'element' => 'Dernier bulletin de paie'])->where('valeur_statut !=', 'TERMINE')
                ->update(['valeur_statut' => 'TERMINE', 'date_realisation' => gmdate('Y-m-d H:i:s')]);
        }
        Audit::log('CALCUL', 'solde_tout_compte', $id, $stc ? ['montant_net' => $stc['montant_net']] : null, $data, 'Calcul du STC');
        return $data;
    }

    /** Modification manuelle des montants en brouillon, puis recomposition du net. */
    public function modifier(int $idRupture, array $post): void
    {
        $stc = $this->db->table('solde_tout_compte')->where('id_rupture', $idRupture)->get()->getRowArray();
        if (! $stc || $stc['statut'] !== 'BROUILLON') {
            throw new \RuntimeException('Le STC n’est modifiable qu’en brouillon.');
        }
        $d = [];
        foreach (['salaire_proratise', 'indemnite_conges', 'indemnite_rupture', 'avances', 'retenues'] as $k) {
            $d[$k] = isset($post[$k]) && is_numeric($post[$k]) ? max(0, (float) $post[$k]) : (float) $stc[$k];
        }
        $d['montant_net'] = round($d['salaire_proratise'] + $d['indemnite_conges'] + $d['indemnite_rupture'] - $d['avances'] - $d['retenues'], 2);
        $this->db->table('solde_tout_compte')->where('id_stc', $stc['id_stc'])->update($d);
        Audit::log('UPDATE', 'solde_tout_compte', (int) $stc['id_stc'], $stc, $d, 'Modification manuelle');
    }

    /** Valide ou paie le STC ; coche la checklist « Validation STC ». */
    public function statut(int $idRupture, string $vers): array
    {
        $stc = $this->db->table('solde_tout_compte')->where('id_rupture', $idRupture)->get()->getRowArray();
        if (! $stc) {
            return [false, 'STC introuvable.'];
        }
        $extra = $vers === 'VALIDE' ? ['date_validation' => gmdate('Y-m-d H:i:s')] : [];
        $res = Transitions::appliquer('solde_tout_compte', (int) $stc['id_stc'], $vers, null, $extra);
        if ($res[0] && $vers === 'VALIDE') {
            $this->db->table('checklist_offboarding')->where(['id_rupture' => $idRupture, 'element' => 'Validation STC'])
                ->update(['valeur_statut' => 'TERMINE', 'date_realisation' => gmdate('Y-m-d H:i:s')]);
        }
        return $res;
    }

    /** PDF du STC (Dompdf), stocké dans pdf_donnees. Retourne [nom, binaire]. */
    public function pdf(int $idRupture): array
    {
        $stc = $this->db->table('solde_tout_compte')->where('id_rupture', $idRupture)->get()->getRowArray();
        $r = $this->db->table('rupture_contrat')->where('id_rupture', $idRupture)->get()->getRowArray();
        if (! $stc || ! $r) {
            throw new \RuntimeException('STC introuvable.');
        }
        if ($stc['statut'] !== 'BROUILLON' && ! empty($stc['pdf_donnees'])) {
            return [$stc['pdf_nom'], $stc['pdf_donnees']];
        }
        $emp = $this->db->table('employe e')->select('e.matricule, e.date_embauche, pe.nom, pe.prenom')->join('personne pe', 'pe.id_personne = e.id_personne')->where('e.id_employe', $r['id_employe'])->get()->getRowArray();
        $html = view('rh/ruptures/stc_pdf', [
            'stc' => $stc, 'r' => $r, 'emp' => $emp, 'entreprise' => config('Entreprise'),
            'lettres' => (new PaieDocumentService())->enLettres((float) $stc['montant_net']),
        ]);
        $pdf = new Dompdf();
        $pdf->loadHtml($html);
        $pdf->setPaper('A4');
        $pdf->render();
        $bin = $pdf->output();
        $nom = 'stc_' . $emp['matricule'] . '.pdf';
        $this->db->table('solde_tout_compte')->where('id_stc', $stc['id_stc'])->update(['pdf_nom' => $nom, 'pdf_type_mime' => 'application/pdf', 'pdf_donnees' => $bin]);
        return [$nom, $bin];
    }
}
