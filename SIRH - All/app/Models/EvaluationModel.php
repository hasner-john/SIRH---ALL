<?php
namespace App\Models;

use CodeIgniter\Model;
use DomainException;
use Throwable;

class EvaluationModel extends Model
{
    protected $table      = 'evaluation_candidat';
    protected $primaryKey = 'id_evaluation';
    protected $returnType = 'array';

    /**
     * $type : DOSSIER | ENTRETIEN | TEST_TECHNIQUE ; $ref = id_entretien / id_test / null.
     * Une évaluation par (candidature, type, référence) : modifiée en place,
     * l'ancienne version est conservée dans historique_formulaire_reponse.
     */
    public function evaluer(int $idCand, string $type, ?int $ref, ?float $note, ?string $comm, int $idEval): void
    {
        if (! in_array($type, ['DOSSIER', 'ENTRETIEN', 'TEST_TECHNIQUE'], true)) {
            throw new DomainException("Type d'évaluation invalide.");
        }
        if ($note !== null && ($note < 0 || $note > 10)) {
            throw new DomainException('La note doit être comprise entre 0 et 10.'); // RG-EVAL-008
        }
        $idEnt  = $type === 'ENTRETIEN' ? $ref : null;
        $idTest = $type === 'TEST_TECHNIQUE' ? $ref : null;
        if ($type !== 'DOSSIER' && ! $ref) {
            throw new DomainException('Référence manquante.');
        }

        $this->db->transBegin();
        try {
            // L'entretien / le test doit appartenir à la candidature ET être REALISE (RG-EVAL-004)
            if ($type === 'ENTRETIEN') {
                $this->verifierRealise('entretien', 'id_entretien', $ref, $idCand, 'Entretien');
            } elseif ($type === 'TEST_TECHNIQUE') {
                $this->verifierRealise('test_technique', 'id_test', $ref, $idCand, 'Test');
            }

            $ex = $this->db->query(
                "SELECT id_evaluation, note, commentaire FROM evaluation_candidat
                 WHERE id_candidature = ? AND type_evaluation = ?
                   AND COALESCE(id_entretien, 0) = COALESCE(?, 0)
                   AND COALESCE(id_test, 0) = COALESCE(?, 0)",
                [$idCand, $type, $idEnt, $idTest]
            )->getRowArray();

            if ($ex) {
                $this->snapshot($idEval, (int) $ex['id_evaluation'], ['note' => $ex['note'], 'commentaire' => $ex['commentaire']], 'MODIFICATION');
                $this->db->table('evaluation_candidat')->where('id_evaluation', $ex['id_evaluation'])->update([
                    'note' => $note, 'commentaire' => $comm, 'id_evaluateur' => $idEval,
                    'date_evaluation' => date('Y-m-d H:i:s'),
                ]);
            } else {
                $this->db->table('evaluation_candidat')->insert([
                    'id_candidature' => $idCand, 'id_evaluateur' => $idEval,
                    'id_entretien' => $idEnt, 'id_test' => $idTest, 'type_evaluation' => $type,
                    'note' => $note, 'commentaire' => $comm,
                ]);
                $this->snapshot($idEval, (int) $this->db->insertID(), ['note' => $note, 'commentaire' => $comm], 'CREATION');
            }
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    private function verifierRealise(string $table, string $pk, int $id, int $idCand, string $label): void
    {
        $r = $this->db->query("SELECT statut, id_candidature FROM $table WHERE $pk = ?", [$id])->getRowArray();
        if (! $r || (int) $r['id_candidature'] !== $idCand) {
            throw new DomainException("$label introuvable pour cette candidature.");
        }
        if ($r['statut'] !== 'REALISE') {
            throw new DomainException("$label non réalisé : évaluation impossible.");
        }
    }

    private function snapshot(int $userId, int $idEvalRow, array $data, string $action): void
    {
        $this->db->table('historique_formulaire_reponse')->insert([
            'id_utilisateur' => $userId, 'type_formulaire' => 'EVALUATION',
            'id_enregistrement' => $idEvalRow, 'donnees' => json_encode($data), 'action' => $action,
        ]);
    }

    public function deCandidature(int $idCand): array
    {
        return $this->db->query(
            "SELECT e.*, u.email AS evaluateur FROM evaluation_candidat e
             JOIN utilisateur u ON u.id_utilisateur = e.id_evaluateur
             WHERE e.id_candidature = ? ORDER BY e.type_evaluation, e.date_evaluation", [$idCand]
        )->getResultArray();
    }

    /** Anciennes versions des notes d'une candidature */
    public function historique(int $idCand): array
    {
        return $this->db->query(
            "SELECT h.action, h.donnees, h.date_action, e.type_evaluation, u.email
             FROM historique_formulaire_reponse h
             JOIN evaluation_candidat e ON e.id_evaluation = h.id_enregistrement
             LEFT JOIN utilisateur u ON u.id_utilisateur = h.id_utilisateur
             WHERE h.type_formulaire = 'EVALUATION' AND e.id_candidature = ?
             ORDER BY h.date_action DESC", [$idCand]
        )->getResultArray();
    }
}
