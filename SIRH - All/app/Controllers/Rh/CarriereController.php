<?php

declare(strict_types=1);

namespace App\Controllers\Rh;

use App\Libraries\Audit;
use App\Libraries\Transitions;
use App\Services\CarriereService;
use App\Services\PropositionCarriereService;

class CarriereController extends RhController
{
    public function index()
    {
        [$n, $erreurs] = (new CarriereService())->appliquerEchus($this->uid());
        $mouvements = $this->db->table('mouvement_carriere m')->select('m.*, e.matricule, pe.nom, pe.prenom')
            ->join('employe e', 'e.id_employe = m.id_employe')->join('personne pe', 'pe.id_personne = e.id_personne')
            ->orderBy('m.date_effet', 'DESC')->orderBy('m.id_mouvement', 'DESC')->get()->getResultArray();
        if ($n > 0) {
            session()->setFlashdata('success', "$n mouvement(s) planifié(s) appliqué(s).");
        }
        if ($erreurs) {
            session()->setFlashdata('error', implode(' | ', $erreurs));
        }
        return view('rh/carriere/index', ['titre' => 'Carrière', 'mouvements' => $mouvements]);
    }

    public function formulaire()
    {
        $idEmploye = (int) $this->request->getGet('employe');
        $prop = null;
        if ((int) $this->request->getGet('proposition') > 0) {
            $prop = $this->db->table('proposition_carriere')->where('id_proposition_carriere', (int) $this->request->getGet('proposition'))->get()->getRowArray();
            $idEmploye = $prop ? (int) $prop['id_employe'] : $idEmploye;
        }
        $actuel = null;
        if ($idEmploye > 0) {
            $actuel = $this->db->query(
                "SELECT e.id_employe, e.id_poste, c.salaire, c.type_contrat, p.libelle AS poste,
                        (SELECT a.fonction FROM affectation a WHERE a.id_employe = e.id_employe AND a.date_fin IS NULL LIMIT 1) AS fonction,
                        (SELECT d.nom FROM affectation a JOIN departement d ON d.id_departement = a.id_departement WHERE a.id_employe = e.id_employe AND a.date_fin IS NULL LIMIT 1) AS departement
                 FROM employe e JOIN contrat c ON c.id_contrat = e.id_contrat LEFT JOIN poste p ON p.id_poste = e.id_poste WHERE e.id_employe = ?", [$idEmploye]
            )->getRowArray();
        }
        $type = $prop ? ['PROMOTION' => 'PROMOTION', 'AUGMENTATION' => 'REVISION_SALAIRE', 'CHANGEMENT_POSTE' => 'CHANGEMENT_POSTE'][$prop['type_proposition']] : null;
        return view('rh/carriere/formulaire', [
            'titre' => 'Mouvement de carrière', 'employes' => $this->employes(), 'idEmploye' => $idEmploye, 'actuel' => $actuel, 'prop' => $prop, 'typePropose' => $type,
            'postes' => $this->db->table('poste')->where('statut', 'ACTIF')->orderBy('libelle')->get()->getResultArray(),
            'departements' => $this->db->table('departement')->where('actif', 1)->get()->getResultArray(),
        ]);
    }

    public function enregistrer()
    {
        $d = $this->request->getPost();
        try {
            [$id, $statut] = (new CarriereService())->creer($d, $this->uid());
        } catch (\Throwable $e) {
            return $this->ko($e->getMessage());
        }
        if ((int) ($d['id_proposition'] ?? 0) > 0) {
            Transitions::appliquer('proposition_carriere', (int) $d['id_proposition'], 'ACCEPTEE', 'Suivie : mouvement #' . $id, ['id_mouvement' => $id, 'id_decideur' => $this->uid(), 'date_decision' => date('Y-m-d H:i:s')]);
        }
        $msg = $statut === 'APPLIQUE' ? 'Mouvement appliqué.' : 'Mouvement planifié (sera appliqué à la date d’effet).';
        if (($d['type_mouvement'] ?? '') === 'REVISION_SALAIRE' && ($d['nouveau_salaire'] ?? '') !== '' && (float) $d['nouveau_salaire'] <= (float) ($d['ancien_affiche'] ?? 0)) {
            $msg .= ' Avertissement : le nouveau salaire n’est pas supérieur à l’ancien.';
        }
        return $this->ok($msg, 'rh/carriere');
    }

    public function annuler(int $id)
    {
        [$ok, $msg] = Transitions::appliquer('mouvement_carriere', $id, 'ANNULE', 'Annulation');
        return $ok ? $this->ok('Mouvement annulé.') : $this->ko($msg);
    }

    public function propositions()
    {
        $rows = $this->db->table('proposition_carriere pc')->select('pc.*, e.matricule, pe.nom, pe.prenom, po.libelle AS poste_suggere')
            ->join('employe e', 'e.id_employe = pc.id_employe')->join('personne pe', 'pe.id_personne = e.id_personne')
            ->join('poste po', 'po.id_poste = pc.id_poste_suggere', 'left')->where('pc.statut', 'EN_ATTENTE')->orderBy('pc.date_creation', 'DESC')->get()->getResultArray();
        return view('rh/carriere/propositions', ['titre' => 'Propositions de carrière', 'rows' => $rows]);
    }

    public function generer()
    {
        $n = (new PropositionCarriereService())->generer();
        Audit::log('RECALCUL', 'proposition_carriere', null, null, ['creees' => $n]);
        return $this->ok("$n nouvelle(s) proposition(s).");
    }

    public function suivre(int $id)
    {
        return redirect()->to(site_url('rh/carriere/mouvement?proposition=' . $id));
    }

    public function refuser(int $id)
    {
        $com = trim((string) $this->request->getPost('commentaire'));
        if ($com === '') {
            return $this->ko('Commentaire obligatoire.');
        }
        [$ok, $msg] = Transitions::appliquer('proposition_carriere', $id, 'REFUSEE', $com, ['commentaire_decision' => $com, 'id_decideur' => $this->uid(), 'date_decision' => date('Y-m-d H:i:s')]);
        return $ok ? $this->ok('Proposition refusée.') : $this->ko($msg);
    }

    /** Parcours et reconstitution de la situation à une date (RG-TRANS-03). */
    public function parcours(int $idEmploye)
    {
        $emp = $this->db->table('employe e')->select('e.*, pe.nom, pe.prenom')->join('personne pe', 'pe.id_personne = e.id_personne')->where('e.id_employe', $idEmploye)->get()->getRowArray();
        if (! $emp) {
            return $this->ko('Employé introuvable.', 'rh/employes');
        }
        $date = (string) $this->request->getGet('date');
        $date = $this->validDate($date) ? $date : date('Y-m-d');
        // contrats de la chaîne de l'employé
        $ids = [];
        $cur = (int) $emp['id_contrat'];
        while ($cur && ! in_array($cur, $ids, true)) {
            $ids[] = $cur;
            $cur = (int) $this->db->table('contrat')->where('id_contrat', $cur)->get()->getRow('id_contrat_precedent');
        }
        $contrat = $this->db->table('contrat c')->select('c.*, p.libelle AS poste')->join('poste p', 'p.id_poste = c.id_poste', 'left')
            ->whereIn('c.id_contrat', $ids)->where('c.date_debut <=', $date)->groupStart()->where('c.date_fin', null)->orWhere('c.date_fin >=', $date)->groupEnd()
            ->orderBy('c.date_debut', 'DESC')->get(1)->getRowArray();
        $aff = $this->db->table('affectation a')->select('a.*, d.nom AS departement')->join('departement d', 'd.id_departement = a.id_departement')
            ->where('a.id_employe', $idEmploye)->where('a.date_debut <=', $date)->groupStart()->where('a.date_fin', null)->orWhere('a.date_fin >=', $date)->groupEnd()
            ->orderBy('a.date_debut', 'DESC')->get(1)->getRowArray();
        $sal = $this->db->table('historique_salaire')->where('id_employe', $idEmploye)->where('date_effet <=', $date)->orderBy('date_effet', 'DESC')->orderBy('id_historique', 'DESC')->get(1)->getRowArray();
        $mouvements = $this->db->table('mouvement_carriere')->where('id_employe', $idEmploye)->orderBy('date_effet')->get()->getResultArray();
        return view('rh/carriere/parcours', ['titre' => 'Parcours', 'emp' => $emp, 'date' => $date, 'contrat' => $contrat, 'aff' => $aff, 'sal' => $sal, 'mouvements' => $mouvements]);
    }
}
