<?php

namespace App\Models;

use CodeIgniter\Model;
use DomainException;
use Throwable;

class RequeteConditionModel extends Model
{
    protected $table      = 'requete_condition_contrat';
    protected $primaryKey = 'id_requete';
    protected $returnType = 'array';
    protected $allowedFields = [
        'id_candidature', 'id_condition', 'valeur_proposee',
        'commentaire_candidat', 'statut',
    ];
    protected $useTimestamps = false;

    /**
     * Conditions CONFIRMER du poste associé à une candidature.
     * Exclut les conditions qui ont déjà une requête active (SOUMISE ou ACCEPTEE_*).
     */
    public function conditionsModifiables(int $idCandidature): array
    {
        return $this->db->query(
            "SELECT c.id_condition, c.libelle, c.type_valeur, c.valeur_condition, c.ordre_affichage
             FROM condition_contrat c
             JOIN poste p ON p.id_poste = c.id_poste
             JOIN demande_recrutement dr ON dr.id_poste = p.id_poste
             JOIN offre_emploi o ON o.id_demande = dr.id_demande
             JOIN candidature ca ON ca.id_offre = o.id_offre
             WHERE ca.id_candidature = ?
               AND c.statut = 'CONFIRMER'
               AND NOT EXISTS (
                   SELECT 1 FROM requete_condition_contrat rq
                   WHERE rq.id_candidature = ca.id_candidature
                     AND rq.id_condition = c.id_condition
                     AND rq.statut IN ('SOUMISE', 'ACCEPTEE_RH', 'ACCEPTEE_DIRECTION')
               )
             ORDER BY c.ordre_affichage",
            [$idCandidature]
        )->getResultArray();
    }

    /** Toutes les conditions confirmées du poste (pour affichage même si déjà requêtées). */
    public function conditionsDuPoste(int $idCandidature): array
    {
        return $this->db->query(
            "SELECT c.id_condition, c.libelle, c.type_valeur, c.valeur_condition, c.ordre_affichage
             FROM condition_contrat c
             JOIN poste p ON p.id_poste = c.id_poste
             JOIN demande_recrutement dr ON dr.id_poste = p.id_poste
             JOIN offre_emploi o ON o.id_demande = dr.id_demande
             JOIN candidature ca ON ca.id_offre = o.id_offre
             WHERE ca.id_candidature = ?
               AND c.statut = 'CONFIRMER'
             ORDER BY c.ordre_affichage",
            [$idCandidature]
        )->getResultArray();
    }

    /** Requêtes existantes pour une candidature, avec le libellé de la condition. */
    public function requetesDe(int $idCandidature): array
    {
        return $this->db->table('requete_condition_contrat rq')
            ->select('rq.*, c.libelle, c.type_valeur, c.valeur_condition')
            ->join('condition_contrat c', 'c.id_condition = rq.id_condition', 'left')
            ->where('rq.id_candidature', $idCandidature)
            ->orderBy('rq.date_creation', 'DESC')
            ->get()->getResultArray();
    }

    /** Historique d'une requête. */
    public function historiqueDe(int $idRequete): array
    {
        return $this->db->table('historique_requete_condition h')
            ->select('h.*, u.email AS auteur')
            ->join('utilisateur u', 'u.id_utilisateur = h.id_utilisateur', 'left')
            ->where('h.id_requete', $idRequete)
            ->orderBy('h.date_action', 'DESC')
            ->get()->getResultArray();
    }
    
    public function candidatsAvecRequetes(): array
    {
        return $this->db->query(
            "SELECT c.id_candidature, p.nom, p.prenom, o.titre,
                    COUNT(*) AS total,
                    SUM(CASE WHEN r.statut = 'SOUMISE' THEN 1 ELSE 0 END) AS a_traiter
             FROM requete_condition_contrat r
             JOIN candidature c ON c.id_candidature = r.id_candidature
             JOIN candidat ca   ON ca.id_candidat = c.id_candidat
             JOIN personne p    ON p.id_personne = ca.id_personne
             JOIN offre_emploi o ON o.id_offre = c.id_offre
             GROUP BY c.id_candidature, p.nom, p.prenom, o.titre
             ORDER BY a_traiter DESC, p.nom"
        )->getResultArray();
    }

    public function deCandidature(int $idCand): array
    {
        return $this->db->query(
            "SELECT r.*, cc.libelle, cc.valeur_condition AS valeur_actuelle
             FROM requete_condition_contrat r
             JOIN condition_contrat cc ON cc.id_condition = r.id_condition
             WHERE r.id_candidature = ?
             ORDER BY cc.ordre_affichage, r.date_creation", [$idCand]
        )->getResultArray();
    }

    /** Décision RH : SOUMISE → ACCEPTEE_RH | REFUSEE_RH (avant la Direction). */
    public function decisionRh(int $idRequete, bool $accepte, int $userId, ?string $comm = null): int
    {
        $nouveau = $accepte ? 'ACCEPTEE_RH' : 'REFUSEE_RH';
        $this->db->transBegin();
        try {
            $row = $this->db->query(
                'SELECT statut, id_candidature FROM requete_condition_contrat WHERE id_requete = ?', [$idRequete]
            )->getRowArray();
            if (! $row) {
                throw new DomainException('Requête introuvable.');
            }
            if ($row['statut'] !== 'SOUMISE') {
                throw new DomainException('Requête déjà traitée par le RH.');
            }
            $this->db->table($this->table)->where('id_requete', $idRequete)
                ->update(['statut' => $nouveau, 'date_modification' => date('Y-m-d H:i:s')]);
            $this->db->table('historique_requete_condition')->insert([
                'id_requete' => $idRequete, 'ancien_statut' => $row['statut'], 'nouveau_statut' => $nouveau,
                'id_utilisateur' => $userId, 'commentaire' => $comm,
            ]);
            $this->db->transCommit();
            return (int) $row['id_candidature'];
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

}