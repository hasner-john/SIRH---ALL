<?php

declare(strict_types=1);

namespace App\Controllers\Rh;

use App\Libraries\Audit;
use App\Libraries\ParametreRh;
use App\Libraries\Transitions;
use App\Services\EvaluationService;
use App\Services\NotificationService;

class FormationsController extends RhController
{
    public function index()
    {
        return view('rh/formations/index', [
            'titre' => 'Formations',
            'formations' => $this->db->table('formation f')->select('f.*, c.code AS competence, (SELECT COUNT(*) FROM formation_participant fp WHERE fp.id_formation = f.id_formation) AS nb')
                ->join('competence c', 'c.id_competence = f.id_competence', 'left')->orderBy('f.date_debut', 'DESC')->get()->getResultArray(),
            'competences' => $this->db->table('competence')->where('actif', 1)->get()->getResultArray(),
        ]);
    }

    public function creer()
    {
        $d = [
            'titre' => trim((string) $this->request->getPost('titre')), 'description' => trim((string) $this->request->getPost('description')),
            'id_competence' => (int) $this->request->getPost('id_competence') ?: null, 'date_debut' => (string) $this->request->getPost('date_debut'),
            'date_fin' => (string) $this->request->getPost('date_fin'), 'prestataire' => trim((string) $this->request->getPost('prestataire')),
            'cout' => (float) $this->request->getPost('cout'),
        ];
        if ($d['titre'] === '' || ! $this->validDate($d['date_debut']) || ! $this->validDate($d['date_fin']) || $d['date_fin'] < $d['date_debut']) {
            return $this->ko('Titre et dates valides requis.');
        }
        $this->db->table('formation')->insert($d);
        Audit::log('CREATE', 'formation', (int) $this->db->insertID(), null, $d);
        return $this->ok('Formation créée.');
    }

    public function fiche(int $id)
    {
        $f = $this->db->table('formation f')->select('f.*, c.libelle AS competence')->join('competence c', 'c.id_competence = f.id_competence', 'left')->where('f.id_formation', $id)->get()->getRowArray();
        if (! $f) {
            return $this->ko('Formation introuvable.', 'rh/formations');
        }
        $parts = $this->db->table('formation_participant fp')->select('fp.*, e.matricule, pe.nom, pe.prenom')
            ->join('employe e', 'e.id_employe = fp.id_employe')->join('personne pe', 'pe.id_personne = e.id_personne')->where('fp.id_formation', $id)->get()->getResultArray();
        return view('rh/formations/fiche', ['titre' => 'Formation : ' . $f['titre'], 'f' => $f, 'parts' => $parts]);
    }

    public function statut(int $id)
    {
        [$ok, $msg] = Transitions::appliquer('formation', $id, (string) $this->request->getPost('statut'));
        return $ok ? $this->ok('Statut de la formation mis à jour.') : $this->ko($msg);
    }

    public function statutParticipant(int $idFormation, int $idParticipant)
    {
        [$ok, $msg] = Transitions::appliquer('formation_participant', $idParticipant, (string) $this->request->getPost('statut'));
        return $ok ? $this->ok('Statut du participant mis à jour.') : $this->ko($msg);
    }

    /** Niveau après formation (participant TERMINE, formation TERMINEE). */
    public function niveauApres(int $id)
    {
        try {
            (new EvaluationService())->niveauApres($id, (int) $this->request->getPost('id_employe'), (int) $this->request->getPost('niveau_apres'), $this->uid());
        } catch (\Throwable $e) {
            return $this->ko($e->getMessage());
        }
        return $this->ok('Niveau après formation enregistré et évaluation post-formation créée.');
    }

    /* ---------------- Propositions ---------------- */

    public function propositions()
    {
        $props = $this->db->table('proposition_formation pf')->select('pf.*, e.matricule, pe.nom, pe.prenom, c.code, c.libelle AS competence')
            ->join('employe e', 'e.id_employe = pf.id_employe')->join('personne pe', 'pe.id_personne = e.id_personne')
            ->join('competence c', 'c.id_competence = pf.id_competence')->where('pf.statut', 'EN_ATTENTE')->orderBy('pf.ecart', 'DESC')->get()->getResultArray();
        $formations = $this->db->table('formation')->whereIn('statut', ['PLANIFIEE', 'EN_COURS'])->get()->getResultArray();
        return view('rh/formations/propositions', ['titre' => 'Propositions de formation', 'props' => $props, 'formations' => $formations, 'seuil' => ParametreRh::get('SEUIL_ECART_FORMATION', 1)]);
    }

    public function assignerProposition(int $id)
    {
        $pf = $this->db->table('proposition_formation')->where('id_proposition_formation', $id)->get()->getRowArray();
        if (! $pf) {
            return $this->ko('Proposition introuvable.');
        }
        $this->db->transBegin();
        try {
            $idF = (int) $this->request->getPost('id_formation');
            if ($idF <= 0) {   // création rapide
                $titre = trim((string) $this->request->getPost('titre'));
                $d1 = (string) $this->request->getPost('date_debut');
                $d2 = (string) $this->request->getPost('date_fin');
                if ($titre === '' || ! $this->validDate($d1) || ! $this->validDate($d2) || $d2 < $d1) {
                    throw new \RuntimeException('Choisissez une formation ou saisissez titre et dates valides.');
                }
                $this->db->table('formation')->insert(['titre' => $titre, 'id_competence' => $pf['id_competence'], 'date_debut' => $d1, 'date_fin' => $d2]);
                $idF = (int) $this->db->insertID();
            }
            $f = $this->db->table('formation')->where('id_formation', $idF)->get()->getRowArray();
            $niveau = $this->db->table('employe_competence')->where(['id_employe' => $pf['id_employe'], 'id_competence' => $pf['id_competence']])
                ->orderBy('date_evaluation', 'DESC')->orderBy('id_employe_competence', 'DESC')->get(1)->getRow('niveau_acquis');
            $this->db->query('INSERT OR IGNORE INTO formation_participant (id_formation, id_employe, statut, niveau_avant) VALUES (?,?,?,?)', [$idF, $pf['id_employe'], 'INSCRIT', $niveau]);
            [$ok, $msg] = Transitions::appliquer('proposition_formation', $id, 'ASSIGNEE', null, ['id_formation' => $idF, 'id_decideur' => $this->uid(), 'date_decision' => date('Y-m-d H:i:s')]);
            if (! $ok) {
                throw new \RuntimeException($msg);
            }
            NotificationService::versEmploye((int) $pf['id_employe'], 'Formation proposée : ' . $f['titre'], 'Du ' . $f['date_debut'] . ' au ' . $f['date_fin'] . ($f['prestataire'] ? ' — ' . $f['prestataire'] : ''), '/employe/formations');
            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            return $this->ko($e->getMessage());
        }
        return $this->ok('Formation assignée et employé notifié.');
    }

    public function refuserProposition(int $id)
    {
        $com = trim((string) $this->request->getPost('commentaire'));
        if ($com === '') {
            return $this->ko('Un commentaire est obligatoire pour refuser.');
        }
        [$ok, $msg] = Transitions::appliquer('proposition_formation', $id, 'REFUSEE', $com, ['commentaire' => $com, 'id_decideur' => $this->uid(), 'date_decision' => date('Y-m-d H:i:s')]);
        return $ok ? $this->ok('Proposition refusée.') : $this->ko($msg);
    }

    public function recalculer()
    {
        $r = (new EvaluationService())->recalculer();
        return $this->ok($r['creees'] . ' proposition(s) créée(s), ' . $r['sans_objet'] . ' passée(s) sans objet.', 'rh/formations/propositions');
    }

    /* ---------------- Assignation directe ---------------- */

    public function assigner()
    {
        return view('rh/formations/assigner', [
            'titre' => 'Assigner une formation',
            'formations' => $this->db->table('formation')->whereIn('statut', ['PLANIFIEE', 'EN_COURS'])->orderBy('date_debut')->get()->getResultArray(),
            'employes' => $this->employes(),
        ]);
    }

    public function enregistrerAssignation()
    {
        $idF = (int) $this->request->getPost('id_formation');
        $ids = array_map('intval', (array) $this->request->getPost('employes'));
        $f = $this->db->table('formation')->where('id_formation', $idF)->whereIn('statut', ['PLANIFIEE', 'EN_COURS'])->get()->getRowArray();
        if (! $f || $ids === []) {
            return $this->ko('Choisissez une formation planifiée ou en cours et au moins un employé.');
        }
        $deja = [];
        $nb = 0;
        $this->db->transBegin();
        foreach ($ids as $idE) {
            if ($this->db->table('formation_participant')->where(['id_formation' => $idF, 'id_employe' => $idE])->countAllResults() > 0) {
                $e = $this->db->table('employe e')->select('e.matricule, pe.nom')->join('personne pe', 'pe.id_personne = e.id_personne')->where('e.id_employe', $idE)->get()->getRowArray();
                $deja[] = ($e['matricule'] ?? $idE) . ' ' . ($e['nom'] ?? '');
                continue;
            }
            $niveau = $f['id_competence'] ? $this->db->table('employe_competence')->where(['id_employe' => $idE, 'id_competence' => $f['id_competence']])
                ->orderBy('date_evaluation', 'DESC')->orderBy('id_employe_competence', 'DESC')->get(1)->getRow('niveau_acquis') : null;
            $this->db->table('formation_participant')->insert(['id_formation' => $idF, 'id_employe' => $idE, 'statut' => 'INSCRIT', 'niveau_avant' => $niveau]);
            NotificationService::versEmploye($idE, 'Formation proposée : ' . $f['titre'], 'Du ' . $f['date_debut'] . ' au ' . $f['date_fin'] . ($f['prestataire'] ? ' — ' . $f['prestataire'] : ''), '/employe/formations');
            $nb++;
        }
        Audit::log('CREATE', 'formation_participant', $idF, null, ['employes' => $ids], 'Assignation');
        $this->db->transCommit();
        $msg = "$nb employé(s) inscrit(s).";
        if ($deja) {
            $msg .= ' Déjà inscrits (ignorés) : ' . implode(', ', $deja) . '.';
        }
        return $this->ok($msg);
    }

    public function apport()
    {
        $rows = $this->db->query(
            'SELECT v.*, e.matricule, pe.nom, pe.prenom, f.titre, c.code
             FROM v_apport_formation v JOIN employe e ON e.id_employe = v.id_employe JOIN personne pe ON pe.id_personne = e.id_personne
             JOIN formation f ON f.id_formation = v.id_formation JOIN competence c ON c.id_competence = v.id_competence ORDER BY v.id_proposition_formation DESC'
        )->getResultArray();
        return view('rh/formations/apport', ['titre' => 'Apport des formations', 'rows' => $rows]);
    }
}
