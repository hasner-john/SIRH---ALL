<?php

declare(strict_types=1);

namespace App\Controllers\Rh;

use App\Libraries\Audit;
use App\Libraries\Transitions;
use App\Services\AttestationService;
use App\Services\RuptureService;
use App\Services\StcService;

class RupturesController extends RhController
{
    public function index()
    {
        (new RuptureService())->appliquerSorties();
        return view('rh/ruptures/index', [
            'titre' => 'Ruptures de contrat',
            'ruptures' => $this->db->table('rupture_contrat r')->select('r.*, e.matricule, pe.nom, pe.prenom, s.montant_net, s.statut AS statut_stc')
                ->join('employe e', 'e.id_employe = r.id_employe')->join('personne pe', 'pe.id_personne = e.id_personne')->join('solde_tout_compte s', 's.id_rupture = r.id_rupture', 'left')
                ->orderBy('r.date_sortie', 'DESC')->get()->getResultArray(),
            'employes' => $this->employes(['ACTIF', 'SUSPENDU']), 'motifs' => RuptureService::MOTIFS,
        ]);
    }

    public function creer()
    {
        try {
            $id = (new RuptureService())->creer($this->request->getPost(), $this->uid());
        } catch (\Throwable $e) {
            return $this->ko($e->getMessage());
        }
        return $this->ok('Rupture enregistrée : checklist, STC brouillon et attestations créés.', 'rh/ruptures/' . $id);
    }

    public function show(int $id)
    {
        $r = $this->db->table('rupture_contrat r')->select('r.*, e.matricule, e.date_embauche, pe.nom, pe.prenom')
            ->join('employe e', 'e.id_employe = r.id_employe')->join('personne pe', 'pe.id_personne = e.id_personne')->where('r.id_rupture', $id)->get()->getRowArray();
        if (! $r) {
            return $this->ko('Rupture introuvable.', 'rh/ruptures');
        }
        Audit::acces('CONSULTATION', 'rupture_contrat', (int) $r['id_employe'], 'Rupture #' . $id);
        return view('rh/ruptures/show', [
            'titre' => 'Rupture #' . $id, 'r' => $r,
            'stc' => $this->db->table('solde_tout_compte')->select('id_stc, id_rupture, dernier_salaire, salaire_proratise, jours_conges_restants, indemnite_conges, indemnite_rupture, avances, retenues, montant_net, statut, id_bulletin_final, date_validation')->where('id_rupture', $id)->get()->getRowArray(),
            'checklist' => $this->db->table('checklist_offboarding')->where('id_rupture', $id)->orderBy('id_checklist')->get()->getResultArray(),
            'attestations' => $this->db->table('attestation_travail')->select('id_attestation, type_attestation, reference, pdf_nom, date_emission')->where('id_rupture', $id)->get()->getResultArray(),
            'direction' => in_array(session()->get('role'), ['DIRECTION', 'FINANCE'], true),
        ]);
    }

    /** Changement de statut de la rupture : RH ; la Direction ne peut que valider (VALIDEE). */
    public function statut(int $id)
    {
        $vers = (string) $this->request->getPost('statut');
        $role = session()->get('role');
        if (in_array($role, ['DIRECTION', 'FINANCE'], true) && $vers !== 'VALIDEE') {
            return $this->ko('La Direction ne peut que valider une rupture.');
        }
        [$ok, $msg] = (new RuptureService())->statut($id, $vers, $this->uid());
        $retour = in_array($role, ['DIRECTION', 'FINANCE'], true) ? 'direction' : 'rh/ruptures/' . $id;
        return $ok ? $this->ok('Statut de la rupture mis à jour.', $retour) : $this->ko($msg, $retour);
    }

    public function checklist(int $id, int $idItem)
    {
        $vers = (string) $this->request->getPost('statut');
        $extra = ['commentaire' => trim((string) $this->request->getPost('commentaire'))];
        if ($vers === 'TERMINE') {
            $extra['date_realisation'] = gmdate('Y-m-d H:i:s');
        }
        $item = $this->db->table('checklist_offboarding')->where(['id_checklist' => $idItem, 'id_rupture' => $id])->get()->getRowArray();
        if (! $item) {
            return $this->ko('Élément introuvable.');
        }
        if ($vers === '' || $vers === $item['valeur_statut']) {   // simple mise à jour du commentaire
            $this->db->table('checklist_offboarding')->where('id_checklist', $idItem)->update($extra);
            return $this->ok('Commentaire enregistré.');
        }
        [$ok, $msg] = Transitions::appliquer('checklist_offboarding', $idItem, $vers, null, $extra);
        return $ok ? $this->ok('Checklist mise à jour.') : $this->ko($msg);
    }

    public function calculerStc(int $id)
    {
        try {
            $svc = new StcService();
            if ($this->request->getPost('manuel')) {
                $svc->modifier($id, $this->request->getPost());
                return $this->ok('STC modifié.');
            }
            $svc->calculer($id);
        } catch (\Throwable $e) {
            return $this->ko($e->getMessage());
        }
        return $this->ok('STC recalculé.');
    }

    public function statutStc(int $id)
    {
        [$ok, $msg] = (new StcService())->statut($id, (string) $this->request->getPost('statut'));
        return $ok ? $this->ok('Statut du STC mis à jour.') : $this->ko($msg);
    }

    public function stcPdf(int $id)
    {
        try {
            [$nom, $bin] = (new StcService())->pdf($id);
        } catch (\Throwable $e) {
            return $this->ko($e->getMessage());
        }
        $emp = $this->db->table('rupture_contrat')->where('id_rupture', $id)->get()->getRow('id_employe');
        Audit::acces('TELECHARGEMENT', 'solde_tout_compte', (int) $emp, 'STC rupture #' . $id);
        return $this->response->setHeader('Content-Type', 'application/pdf')->setHeader('Content-Disposition', 'inline; filename="' . $nom . '"')->setBody($bin);
    }

    public function attestationPdf(int $id, int $idAttestation)
    {
        $a = $this->db->table('attestation_travail')->where(['id_attestation' => $idAttestation, 'id_rupture' => $id])->get()->getRowArray();
        if (! $a) {
            return $this->ko('Attestation introuvable.');
        }
        try {
            [$nom, $bin] = (new AttestationService())->generer($idAttestation);
        } catch (\Throwable $e) {
            return $this->ko($e->getMessage());
        }
        return $this->response->setHeader('Content-Type', 'application/pdf')->setHeader('Content-Disposition', 'inline; filename="' . $nom . '"')->setBody($bin);
    }
}
