<?php

declare(strict_types=1);

namespace App\Controllers\Rh;

use App\Libraries\ParametreRh;
use App\Services\EvaluationService;

class PerformanceController extends RhController
{
    public function index()
    {
        $q = $this->db->table('evaluation_performance ev')->select('ev.*, e.matricule, pe.nom, pe.prenom')
            ->join('employe e', 'e.id_employe = ev.id_employe')->join('personne pe', 'pe.id_personne = e.id_personne');
        foreach (['id_employe' => 'ev.id_employe', 'statut' => 'ev.statut', 'type_evaluation' => 'ev.type_evaluation', 'periode' => 'ev.periode'] as $g => $col) {
            if ($this->request->getGet($g)) {
                $q->where($col, $this->request->getGet($g));
            }
        }
        return view('rh/performance/index', [
            'titre' => 'Performance', 'evaluations' => $q->orderBy('ev.date_evaluation', 'DESC')->orderBy('ev.id_evaluation_performance', 'DESC')->get()->getResultArray(),
            'employes' => $this->employes(), 'filtre' => $this->request->getGet(), 'seuil' => ParametreRh::get('SEUIL_ECART_FORMATION', 1),
        ]);
    }

    public function nouvelle(int $idEmploye)
    {
        $emp = $this->db->table('employe e')->select('e.*, pe.nom, pe.prenom')->join('personne pe', 'pe.id_personne = e.id_personne')->where('e.id_employe', $idEmploye)->get()->getRowArray();
        if (! $emp) {
            return $this->ko('Employé introuvable.', 'rh/performance');
        }
        $competences = $this->db->query(
            'SELECT c.id_competence, c.code, c.libelle, pc.niveau_attendu,
                    (SELECT ec.niveau_acquis FROM employe_competence ec WHERE ec.id_employe = ? AND ec.id_competence = c.id_competence ORDER BY ec.date_evaluation DESC, ec.id_employe_competence DESC LIMIT 1) AS dernier
             FROM poste_competence pc JOIN competence c ON c.id_competence = pc.id_competence WHERE pc.id_poste = ? AND c.actif = 1',
            [$idEmploye, $emp['id_poste']]
        )->getResultArray();
        return view('rh/performance/nouvelle', [
            'titre' => 'Nouvelle évaluation', 'emp' => $emp, 'competences' => $competences,
            'hors' => $this->db->table('competence')->where('actif', 1)->get()->getResultArray(),
            'objectifs' => $this->db->table('objectif')->where('id_employe', $idEmploye)->whereIn('statut', ['OUVERT', 'EN_COURS'])->get()->getResultArray(),
        ]);
    }

    public function enregistrer()
    {
        $p = $this->request->getPost();
        $niveaux = array_filter((array) ($p['niveau'] ?? []), static fn ($v) => $v !== '' && $v !== null);
        if (! empty($p['hors_competence']) && ! empty($p['hors_niveau'])) {
            $niveaux[(int) $p['hors_competence']] = (int) $p['hors_niveau'];
        }
        if ((int) ($p['id_employe'] ?? 0) <= 0 || trim((string) ($p['periode'] ?? '')) === '' || $niveaux === []) {
            return $this->ko('Employé, période et au moins un niveau acquis requis.');
        }
        if (($p['note_globale'] ?? '') !== '' && ((float) $p['note_globale'] < 0 || (float) $p['note_globale'] > 20)) {
            return $this->ko('La note globale doit être comprise entre 0 et 20.');
        }
        try {
            $id = (new EvaluationService())->enregistrer($p + ['note_globale' => $p['note_globale'] ?? ''], $niveaux, (array) ($p['objectif'] ?? []), $this->uid());
        } catch (\Throwable $e) {
            return $this->ko($e->getMessage());
        }
        return $this->ok('Évaluation enregistrée en brouillon.', 'rh/performance/' . $id);
    }

    public function valider(int $id)
    {
        try {
            $n = (new EvaluationService())->valider($id, $this->uid());
        } catch (\Throwable $e) {
            return $this->ko($e->getMessage());
        }
        return $this->ok("Évaluation validée. $n proposition(s) de formation créée(s).");
    }

    public function detail(int $id)
    {
        $ev = $this->db->table('evaluation_performance ev')->select('ev.*, e.matricule, e.id_poste, pe.nom, pe.prenom')
            ->join('employe e', 'e.id_employe = ev.id_employe')->join('personne pe', 'pe.id_personne = e.id_personne')
            ->where('ev.id_evaluation_performance', $id)->get()->getRowArray();
        if (! $ev) {
            return $this->ko('Évaluation introuvable.', 'rh/performance');
        }
        // Précédente évaluation du même employé
        $prec = $this->db->table('evaluation_performance')->where('id_employe', $ev['id_employe'])->where('id_evaluation_performance <', $id)
            ->whereIn('statut', ['VALIDE', 'ARCHIVE'])->orderBy('id_evaluation_performance', 'DESC')->get(1)->getRowArray();
        $lignes = $this->db->query(
            'SELECT c.code, c.libelle, ec.niveau_acquis, pc.niveau_attendu, ecc.ecart,
                    (SELECT e2.niveau_acquis FROM ecart_competence e2 WHERE e2.id_evaluation_performance = ? AND e2.id_competence = ec.id_competence) AS precedent
             FROM employe_competence ec JOIN competence c ON c.id_competence = ec.id_competence
             LEFT JOIN poste_competence pc ON pc.id_competence = ec.id_competence AND pc.id_poste = ?
             LEFT JOIN ecart_competence ecc ON ecc.id_evaluation_performance = ec.id_evaluation_performance AND ecc.id_competence = ec.id_competence
             WHERE ec.id_evaluation_performance = ?',
            [$prec['id_evaluation_performance'] ?? 0, $ev['id_poste'], $id]
        )->getResultArray();
        $props = $this->db->table('proposition_formation pf')->select('pf.*, c.libelle')->join('competence c', 'c.id_competence = pf.id_competence')
            ->join('ecart_competence e', 'e.id_ecart = pf.id_ecart')->where('e.id_evaluation_performance', $id)->get()->getResultArray();
        return view('rh/performance/detail', [
            'titre' => 'Évaluation #' . $id, 'ev' => $ev, 'lignes' => $lignes, 'props' => $props, 'prec' => $prec,
            'seuil' => ParametreRh::get('SEUIL_ECART_FORMATION', 1),
        ]);
    }
}
