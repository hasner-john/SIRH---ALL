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
            'titre' => 'Paie, compétences, carrière, discipline et offboarding',
            'periodes' => $db->table('periode_paie')->orderBy('annee', 'DESC')->orderBy('mois', 'DESC')->get()->getResultArray(),
            'bulletins' => $db->table('bulletin_paie bp')
                ->select('bp.*, e.matricule, pe.nom, pe.prenom')
                ->join('employe e', 'e.id_employe = bp.id_employe')
                ->join('personne pe', 'pe.id_personne = e.id_personne')
                ->orderBy('bp.id_bulletin', 'DESC')->get()->getResultArray(),
            'competences' => $db->table('competence')->get()->getResultArray(),
            'formations' => $db->table('formation')->orderBy('date_debut', 'DESC')->get()->getResultArray(),
            'objectifs' => $db->table('objectif o')->select('o.*, e.matricule, pe.nom, pe.prenom')
                ->join('employe e', 'e.id_employe = o.id_employe')
                ->join('personne pe', 'pe.id_personne = e.id_personne')
                ->get()->getResultArray(),
            'mouvements' => $db->table('mouvement_carriere m')
                ->select('m.*, e.matricule, pe.nom, pe.prenom')
                ->join('employe e', 'e.id_employe = m.id_employe')
                ->join('personne pe', 'pe.id_personne = e.id_personne')
                ->orderBy('m.date_effet', 'DESC')->get()->getResultArray(),
            'incidents' => $db->table('incident i')
                ->select('i.*, e.matricule, pe.nom, pe.prenom')
                ->join('employe e', 'e.id_employe = i.id_employe')
                ->join('personne pe', 'pe.id_personne = e.id_personne')
                ->orderBy('i.date_incident', 'DESC')->get()->getResultArray(),
            'ruptures' => $db->table('rupture_contrat r')
                ->select('r.*, e.matricule, pe.nom, pe.prenom')
                ->join('employe e', 'e.id_employe = r.id_employe')
                ->join('personne pe', 'pe.id_personne = e.id_personne')
                ->orderBy('r.date_sortie', 'DESC')->get()->getResultArray(),
            'employes' => $db->table('employe e')
                ->select('e.id_employe,e.matricule,pe.nom,pe.prenom')
                ->join('personne pe', 'pe.id_personne = e.id_personne')
                ->where('e.statut', 'ACTIF')->get()->getResultArray(),
        ]);
    }

    public function calculerPaie()
    {
        try {
            $n = (new PaieService())->calculerPeriode(
                (int) $this->request->getPost('id_periode'),
                (int) session()->get('id_utilisateur'),
                $this->request->getIPAddress()
            );
            return redirect()->back()->with('success', $n . ' bulletin(s) calculé(s).');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function validerPaie()
    {
        $db = \Config\Database::connect();
        $id = (int) $this->request->getPost('id_periode');
        $p = $db->table('periode_paie')->where('id_periode', $id)->get()->getRowArray();
        if (! $p || $p['statut'] !== 'CALCUL') {
            return redirect()->back()->with('error', 'La période doit être calculée avant validation.');
        }
        $db->transStart();
        $db->table('bulletin_paie')->where('id_periode', $id)->where('statut', 'BROUILLON')->update(['statut' => 'VALIDE']);
        $db->table('periode_paie')->where('id_periode', $id)->update(['statut' => 'VALIDE']);
        $db->transComplete();
        if (! $db->transStatus()) {
            return redirect()->back()->with('error', 'Validation impossible.');
        }
        $this->audit('UPDATE', 'periode_paie', $id, 'CALCUL', 'VALIDE');
        return redirect()->back()->with('success', 'Période validée : les bulletins sont verrouillés.');
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

    public function creationCarriere()
    {
        $db = \Config\Database::connect();
        $data = [
            'id_employe' => (int) $this->request->getPost('id_employe'),
            'type_mouvement' => (string) $this->request->getPost('type_mouvement'),
            'nouveau_poste' => trim((string) $this->request->getPost('nouveau_poste')),
            'nouveau_salaire' => $this->request->getPost('nouveau_salaire') ?: null,
            'id_departement' => $this->request->getPost('id_departement') ?: null,
            'date_effet' => (string) $this->request->getPost('date_effet'),
            'motif' => trim((string) $this->request->getPost('motif')),
            'id_auteur' => (int) session()->get('id_utilisateur'),
        ];
        if ($data['id_employe'] <= 0 || $data['date_effet'] === '' || $data['nouveau_poste'] === '') {
            return redirect()->back()->with('error', 'Employé, poste et date d’effet requis.');
        }
        $db->table('mouvement_carriere')->insert($data);
        $this->audit('CREATE', 'mouvement_carriere', null, null, $data['motif']);
        return redirect()->back()->with('success', 'Mouvement de carrière enregistré.');
    }

    public function creerFormation()
    {
        $db = \Config\Database::connect();
        $data = [
            'titre' => trim((string) $this->request->getPost('titre')),
            'description' => trim((string) $this->request->getPost('description')),
            'date_debut' => (string) $this->request->getPost('date_debut'),
            'date_fin' => (string) $this->request->getPost('date_fin'),
            'prestataire' => trim((string) $this->request->getPost('prestataire')),
            'cout' => (float) $this->request->getPost('cout'),
        ];
        if ($data['titre'] === '' || $data['date_fin'] < $data['date_debut']) {
            return redirect()->back()->with('error', 'Formation invalide.');
        }
        $db->table('formation')->insert($data);
        $id = (int) $db->insertID();
        $this->audit('CREATE', 'formation', (int) $id, null, 'Création du plan de formation');
        return redirect()->back()->with('success', 'Formation créée.');
    }

    public function creerIncident()
    {
        $db = \Config\Database::connect();
        $data = [
            'id_employe' => (int) $this->request->getPost('id_employe'),
            'date_incident' => (string) $this->request->getPost('date_incident'),
            'objet' => trim((string) $this->request->getPost('objet')),
            'description' => trim((string) $this->request->getPost('description')),
            'id_signaleur' => (int) session()->get('id_utilisateur'),
        ];
        if ($data['id_employe'] <= 0 || $data['objet'] === '' || $data['description'] === '') {
            return redirect()->back()->with('error', 'Informations de l’incident incomplètes.');
        }
        $db->table('incident')->insert($data);
        $id = (int) $db->insertID();
        $this->audit('CREATE', 'incident', (int) $id, null, 'Signalement disciplinaire');
        return redirect()->back()->with('success', 'Incident signalé. La procédure contradictoire peut être ouverte.');
    }

    public function demanderExplication()
    {
        $db = \Config\Database::connect();
        $id = (int) $this->request->getPost('id_incident');
        $dateDemande = (string) $this->request->getPost('date_demande');
        $dateLimite = (string) $this->request->getPost('date_limite');
        if ($id <= 0 || $dateLimite < $dateDemande) {
            return redirect()->back()->with('error', 'Délai de réponse invalide.');
        }
        $db->table('demande_explication')->insert([
            'id_incident' => $id,
            'date_demande' => $dateDemande,
            'date_limite' => $dateLimite,
            'texte' => trim((string) $this->request->getPost('texte')),
            'date_notification' => date('Y-m-d H:i:s'),
        ]);
        $db->table('incident')->where('id_incident', $id)->update(['statut' => 'EXPLICATIONS']);
        $this->audit('UPDATE', 'incident', $id, 'SIGNALE', 'EXPLICATIONS');
        return redirect()->back()->with('success', 'Demande d’explications enregistrée.');
    }

    public function creerRupture()
    {
        $db = \Config\Database::connect();
        $data = [
            'id_employe' => (int) $this->request->getPost('id_employe'),
            'motif' => (string) $this->request->getPost('motif'),
            'date_notification' => (string) $this->request->getPost('date_notification'),
            'date_sortie' => (string) $this->request->getPost('date_sortie'),
            'motif_detail' => trim((string) $this->request->getPost('motif_detail')),
            'id_auteur' => (int) session()->get('id_utilisateur'),
        ];
        if ($data['id_employe'] <= 0 || $data['date_sortie'] < $data['date_notification']) {
            return redirect()->back()->with('error', 'Dates de rupture invalides.');
        }
        $db->transBegin();
        $inserted = $db->table('rupture_contrat')->insert($data);
        if (! $inserted) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Impossible d’enregistrer la rupture.');
        }
        $id = (int) $db->insertID();
        if ($id <= 0) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Identifiant de rupture invalide.');
        }
        $elements = ['STC','Dernier bulletin','Certificat de travail','Attestations sociales','Restitution matériel','Coupure des accès','Archivage du dossier'];
        $rows = [];
        foreach ($elements as $element) {
            $rows[] = ['id_rupture' => $id, 'element' => $element, 'valeur_statut' => 'A_FAIRE'];
        }
        $db->table('checklist_offboarding')->insertBatch($rows);
        $contract = $db->table('employe e')
            ->select('c.salaire')
            ->join('contrat c', 'c.id_contrat = e.id_contrat')
            ->where('e.id_employe', $data['id_employe'])
            ->get()->getRowArray();
        $salaire = (float) ($contract['salaire'] ?? 0);
        $db->table('solde_tout_compte')->insert([
            'id_rupture' => $id,
            'dernier_salaire' => $salaire,
            'salaire_proratise' => $salaire,
            'indemnite_conges' => 0,
            'indemnite_rupture' => 0,
            'avances' => 0,
            'retenues' => 0,
            'montant_net' => $salaire,
            'statut' => 'BROUILLON',
        ]);
        foreach ([
            'CERTIFICAT_TRAVAIL' => 'CT-'.$id,
            'ATTESTATION_CNAPS' => 'CNAPS-'.$id,
            'ATTESTATION_OSTIE' => 'OSTIE-'.$id,
        ] as $type => $reference) {
            $db->table('attestation_travail')->insert([
                'id_rupture' => $id,
                'type_attestation' => $type,
                'reference' => $reference,
                'pdf_nom' => null,
                'pdf_type_mime' => null,
                'pdf_donnees' => null,
            ]);
        }
        if (! $db->transStatus()) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Impossible de créer la checklist d’offboarding.');
        }
        $db->transCommit();
        $this->audit('CREATE', 'rupture_contrat', (int) $id, null, $data['motif_detail']);
        return redirect()->back()->with('success', 'Rupture enregistrée et checklist créée.');
    }

    public function repondreExplication()
    {
        $db = \Config\Database::connect();
        $idIncident = (int) $this->request->getPost('id_incident');
        $texte = trim((string) $this->request->getPost('texte'));
        if ($idIncident <= 0 || $texte === '') {
            return redirect()->back()->with('error', 'Réponse contradictoire invalide.');
        }
        $db->transBegin();
        $db->table('reponse_explication')->insert([
            'id_incident' => $idIncident,
            'date_reponse' => (string) $this->request->getPost('date_reponse'),
            'texte' => $texte,
        ]);
        $db->table('incident')->where('id_incident', $idIncident)->update(['statut' => 'REPONSE']);
        $db->transComplete();
        if (! $db->transStatus()) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Impossible d’enregistrer la réponse.');
        }
        $this->audit('CREATE', 'reponse_explication', $idIncident, null, 'Réponse de l’employé');
        return redirect()->back()->with('success', 'Réponse de l’employé enregistrée.');
    }

    public function sanctionner()
    {
        $db = \Config\Database::connect();
        $idIncident = (int) $this->request->getPost('id_incident');
        $type = (string) $this->request->getPost('type_sanction');
        $motif = trim((string) $this->request->getPost('motif'));
        $validTypes = ['AVERTISSEMENT', 'BLAME', 'MISE_A_PIED', 'LICENCEMENT', 'AUTRE'];
        if ($idIncident <= 0 || ! in_array($type, $validTypes, true) || $motif === '') {
            return redirect()->back()->with('error', 'Décision disciplinaire invalide.');
        }
        $reponse = $db->table('reponse_explication')->where('id_incident', $idIncident)->get()->getRowArray();
        if (! $reponse) {
            return redirect()->back()->with('error', 'Aucune réponse contradictoire : sanction interdite.');
        }
        $db->transBegin();
        $db->table('sanction')->insert([
            'id_incident' => $idIncident,
            'type_sanction' => $type,
            'motif' => $motif,
            'date_decision' => (string) $this->request->getPost('date_decision'),
            'date_effet' => (string) $this->request->getPost('date_effet'),
            'id_decideur' => (int) session()->get('id_utilisateur'),
            'statut' => 'NOTIFIEE',
        ]);
        $db->table('incident')->where('id_incident', $idIncident)->update(['statut' => 'SANCTION']);
        $db->transComplete();
        if (! $db->transStatus()) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Impossible d’enregistrer la sanction.');
        }
        $this->audit('CREATE', 'sanction', $idIncident, null, $motif);
        return redirect()->back()->with('success', 'Sanction enregistrée après procédure contradictoire.');
    }

    private function audit(string $action, string $objet, ?int $objetId, ?string $ancienne, ?string $nouvelle): void
    {
        \Config\Database::connect()->table('audit_rh')->insert([
            'id_utilisateur' => (int) session()->get('id_utilisateur'),
            'action' => $action,
            'objet' => $objet,
            'objet_id' => $objetId,
            'ancienne_valeur' => $ancienne,
            'nouvelle_valeur' => $nouvelle,
            'adresse_ip' => $this->request->getIPAddress(),
        ]);
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
