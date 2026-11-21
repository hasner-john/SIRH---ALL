<?php

declare(strict_types=1);

namespace App\Controllers\Rh;

use App\Libraries\Audit;
use App\Libraries\ParametreRh;
use App\Services\DisciplineService;

class DisciplineController extends RhController
{
    public function incidents()
    {
        $q = $this->db->table('incident i')->select('i.*, e.matricule, pe.nom, pe.prenom')
            ->join('employe e', 'e.id_employe = i.id_employe')->join('personne pe', 'pe.id_personne = e.id_personne');
        foreach (['statut' => 'i.statut', 'gravite' => 'i.gravite', 'categorie' => 'i.categorie', 'id_employe' => 'i.id_employe'] as $g => $col) {
            if ($this->request->getGet($g)) {
                $q->where($col, $this->request->getGet($g));
            }
        }
        if ($this->request->getGet('du')) {
            $q->where('i.date_incident >=', $this->request->getGet('du'));
        }
        if ($this->request->getGet('au')) {
            $q->where('i.date_incident <=', $this->request->getGet('au'));
        }
        Audit::acces('CONSULTATION', 'DOSSIER_DISCIPLINAIRE', null, 'Liste des incidents');
        return view('rh/discipline/incidents', [
            'titre' => 'Incidents disciplinaires', 'incidents' => $q->orderBy('i.date_incident', 'DESC')->get()->getResultArray(),
            'employes' => $this->employes(['ACTIF', 'SUSPENDU']), 'filtre' => $this->request->getGet(),
        ]);
    }

    public function creerIncident()
    {
        try {
            $id = (new DisciplineService())->creerIncident($this->request->getPost(), $this->uid());
        } catch (\Throwable $e) {
            return $this->ko($e->getMessage());
        }
        return $this->ok('Incident signalé.', 'rh/discipline/incidents/' . $id);
    }

    public function dossier(int $id)
    {
        $i = $this->db->table('incident i')->select('i.*, e.matricule, pe.nom, pe.prenom')->join('employe e', 'e.id_employe = i.id_employe')
            ->join('personne pe', 'pe.id_personne = e.id_personne')->where('i.id_incident', $id)->get()->getRowArray();
        if (! $i) {
            return $this->ko('Incident introuvable.', 'rh/discipline/incidents');
        }
        $svc = new DisciplineService();
        Audit::acces('CONSULTATION', 'DOSSIER_DISCIPLINAIRE', (int) $i['id_employe'], 'Incident #' . $id);
        $dem = $this->db->table('demande_explication')->where('id_incident', $id)->get()->getRowArray();
        return view('rh/discipline/dossier', [
            'titre' => 'Dossier #' . $id, 'i' => $i, 'dem' => $dem,
            'rep' => $this->db->table('reponse_explication')->select('id_reponse, date_reponse, texte, en_retard, piece_jointe_nom')->where('id_incident', $id)->get()->getRowArray(),
            'sanction' => $this->db->table('sanction')->where('id_incident', $id)->get()->getRowArray(),
            'peutDecider' => $svc->conditionRemplie($id) && in_array($i['statut'], ['EXPLICATIONS', 'REPONSE', 'COMMISSION'], true),
            'types' => $svc->typesAutorises($i['gravite']), 'recidive' => $svc->recidive((int) $i['id_employe']),
            'delaiDefaut' => (int) ParametreRh::get('DISC_DELAI_REPONSE_JOURS', 5), 'recidiveMois' => (int) ParametreRh::get('DISC_RECIDIVE_MOIS', 12),
        ]);
    }

    public function demanderExplication(int $id)
    {
        try {
            (new DisciplineService())->demanderExplication($id, (string) $this->request->getPost('date_limite'), (string) $this->request->getPost('texte'), $this->uid());
        } catch (\Throwable $e) {
            return $this->ko($e->getMessage());
        }
        return $this->ok('Demande d’explications envoyée à l’employé.');
    }

    /** Réponse papier saisie par la RH au nom de l'employé. */
    public function saisirReponse(int $id)
    {
        try {
            (new DisciplineService())->enregistrerReponse($id, (string) $this->request->getPost('texte'), (string) $this->request->getPost('date_reponse'), $this->request->getFile('piece'), $this->uid());
        } catch (\Throwable $e) {
            return $this->ko($e->getMessage());
        }
        return $this->ok('Réponse enregistrée.');
    }

    public function commission(int $id)
    {
        try {
            (new DisciplineService())->commission($id, (string) $this->request->getPost('date_commission'), (string) $this->request->getPost('commentaire'));
        } catch (\Throwable $e) {
            return $this->ko($e->getMessage());
        }
        return $this->ok('Dossier passé en commission.');
    }

    public function decider(int $id)
    {
        try {
            (new DisciplineService())->decider($id, $this->request->getPost(), $this->uid());
        } catch (\Throwable $e) {
            return $this->ko($e->getMessage());
        }
        return $this->ok('Décision enregistrée.');
    }

    public function sanctions()
    {
        $q = $this->db->table('sanction s')->select('s.*, i.gravite, i.objet, i.id_employe, e.matricule, pe.nom, pe.prenom, d.nom AS departement')
            ->join('incident i', 'i.id_incident = s.id_incident')->join('employe e', 'e.id_employe = i.id_employe')->join('personne pe', 'pe.id_personne = e.id_personne')
            ->join('affectation a', 'a.id_employe = e.id_employe AND a.date_fin IS NULL', 'left')->join('departement d', 'd.id_departement = a.id_departement', 'left');
        foreach (['type_sanction' => 's.type_sanction', 'statut' => 's.statut', 'id_employe' => 'i.id_employe', 'gravite' => 'i.gravite', 'id_departement' => 'a.id_departement'] as $g => $col) {
            if ($this->request->getGet($g)) {
                $q->where($col, $this->request->getGet($g));
            }
        }
        if ($this->request->getGet('du')) {
            $q->where('s.date_decision >=', $this->request->getGet('du'));
        }
        if ($this->request->getGet('au')) {
            $q->where('s.date_decision <=', $this->request->getGet('au'));
        }
        Audit::acces('CONSULTATION', 'DOSSIER_DISCIPLINAIRE', null, 'Liste des sanctions');
        return view('rh/discipline/sanctions', [
            'titre' => 'Sanctions', 'sanctions' => $q->orderBy('s.date_decision', 'DESC')->get()->getResultArray(), 'employes' => $this->employes(['ACTIF', 'SUSPENDU', 'PARTI']),
            'departements' => $this->db->table('departement')->get()->getResultArray(), 'filtre' => $this->request->getGet(),
        ]);
    }

    public function statutSanction(int $id)
    {
        try {
            (new DisciplineService())->statutSanction($id, (string) $this->request->getPost('statut'), (string) $this->request->getPost('commentaire'));
        } catch (\Throwable $e) {
            return $this->ko($e->getMessage());
        }
        return $this->ok('Statut de la sanction mis à jour.');
    }

    public function reponses()
    {
        $q = $this->db->table('reponse_explication r')->select('r.id_reponse, r.id_incident, r.date_reponse, r.texte, r.en_retard, r.piece_jointe_nom, i.objet, i.id_employe, e.matricule, pe.nom, pe.prenom')
            ->join('incident i', 'i.id_incident = r.id_incident')->join('employe e', 'e.id_employe = i.id_employe')->join('personne pe', 'pe.id_personne = e.id_personne');
        if ($this->request->getGet('id_employe')) {
            $q->where('i.id_employe', $this->request->getGet('id_employe'));
        }
        if ($this->request->getGet('du')) {
            $q->where('r.date_reponse >=', $this->request->getGet('du'));
        }
        if ($this->request->getGet('au')) {
            $q->where('r.date_reponse <=', $this->request->getGet('au'));
        }
        if ($this->request->getGet('piece') === '1') {
            $q->where('r.piece_jointe_nom IS NOT NULL', null, false);
        } elseif ($this->request->getGet('piece') === '0') {
            $q->where('r.piece_jointe_nom IS NULL', null, false);
        }
        if ($this->request->getGet('en_retard') === '1') {
            $q->where('r.en_retard', 1);
        }
        Audit::acces('CONSULTATION', 'DOSSIER_DISCIPLINAIRE', null, 'Liste des réponses');
        return view('rh/discipline/reponses', [
            'titre' => 'Réponses des employés', 'reponses' => $q->orderBy('r.date_reponse', 'DESC')->get()->getResultArray(),
            'employes' => $this->employes(['ACTIF', 'SUSPENDU', 'PARTI']), 'filtre' => $this->request->getGet(),
        ]);
    }

    public function piece(int $idReponse)
    {
        $r = $this->db->table('reponse_explication r')->select('r.*, i.id_employe')->join('incident i', 'i.id_incident = r.id_incident')->where('r.id_reponse', $idReponse)->get()->getRowArray();
        if (! $r || empty($r['piece_jointe_donnees'])) {
            return $this->ko('Pièce jointe introuvable.');
        }
        Audit::acces('TELECHARGEMENT', 'DOSSIER_DISCIPLINAIRE', (int) $r['id_employe'], 'Pièce jointe réponse #' . $idReponse);
        return $this->response->setHeader('Content-Type', $r['piece_jointe_mime'])->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Content-Disposition', 'attachment; filename="' . addslashes((string) $r['piece_jointe_nom']) . '"')->setBody($r['piece_jointe_donnees']);
    }
}
