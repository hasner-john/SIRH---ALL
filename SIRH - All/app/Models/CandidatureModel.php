<?php

namespace App\Models;

use CodeIgniter\Model;
use DomainException;
use Throwable;

class CandidatureModel extends Model
{
    protected $table      = 'candidature';
    protected $primaryKey = 'id_candidature';
    protected $returnType = 'array';
    // protected $allowedFields = ['statut'];
    protected $allowedFields = [
        'id_offre',
        'id_candidat',
        'statut',
        'cv_nom',
        'cv_type_mime',
        'cv_donnees',
        'lettre_nom',
        'lettre_type_mime',
        'lettre_donnees',
        'cin_nom',
        'cin_type_mime',
        'cin_donnees',
        'motivation',
        'date_candidature',
    ];

    protected $useTimestamps = false;

    /**
     * Récupérer toutes les candidatures d'une offre.
     */
    public function getCandidaturesParOffre($idOffre)
    {
        return $this->where('id_offre', $idOffre)
                    ->orderBy('date_candidature', 'DESC')
                    ->findAll();
    }

    /**
     * Récupérer une candidature par son identifiant.
     */
    public function getCandidatureAvecDetails($idCandidature)
    {
        return $this->select('candidature.*')
                    ->where('id_candidature', $idCandidature)
                    ->first();
    }

    /**
     * Modifier le statut d'une candidature.
     */
    public function changerStatut($idCandidature, $statut)
    {
        return $this->update($idCandidature, [
            'statut' => $statut,
        ]);
    }


    
    /** Liste enrichie avec candidat + offre. */
    public function listeEnrichie(?string $statut = null): array
    {
        $b = $this->db->table('candidature c')
            ->select('c.id_candidature, c.statut, c.date_candidature,
                      c.cv_nom, c.lettre_nom, c.cin_nom,
                      p.nom, p.prenom, p.telephone,
                      o.titre AS offre_titre, o.statut AS offre_statut')
            ->join('candidat ca', 'ca.id_candidat = c.id_candidat', 'left')
            ->join('personne p', 'p.id_personne = ca.id_personne', 'left')
            ->join('offre_emploi o', 'o.id_offre = c.id_offre', 'left')
            ->orderBy('c.date_candidature', 'DESC');

        if ($statut) {
            $b->where('c.statut', $statut);
        }
        return $b->get()->getResultArray();
    }

    /** Détail complet d'une candidature. */
    public function trouverDetaillee(int $id): ?array
    {
        return $this->db->table('candidature c')
            ->select('c.*, p.nom, p.prenom, p.telephone, p.adresse, p.date_naissance,
                      u.id_utilisateur AS id_utilisateur_candidat, u.email AS email_candidat,
                      ca.niveau_etude, ca.experience,
                      o.id_offre, o.titre AS offre_titre, o.statut AS offre_statut,
                      d.id_demande, d.id_poste,
                      po.libelle AS poste_libelle, dep.nom AS departement_nom')
            ->join('candidat ca', 'ca.id_candidat = c.id_candidat', 'left')
            ->join('personne p', 'p.id_personne = ca.id_personne', 'left')
            ->join('utilisateur u', 'u.id_utilisateur = p.id_utilisateur', 'left')
            ->join('offre_emploi o', 'o.id_offre = c.id_offre', 'left')
            ->join('demande_recrutement d', 'd.id_demande = o.id_demande', 'left')
            ->join('poste po', 'po.id_poste = d.id_poste', 'left')
            ->join('departement dep', 'dep.id_departement = po.id_departement', 'left')
            ->where('c.id_candidature', $id)
            ->get()->getRowArray();
    }

    /** Évaluations RH de tous les types. */
    public function evaluationsDe(int $idCandidature): array
    {
        return $this->db->table('evaluation_candidat ev')
            ->select('ev.*, u.email AS evaluateur_email,
                      e.date_entretien, e.lieu AS entretien_lieu,
                      t.intitule AS test_intitule')
            ->join('utilisateur u', 'u.id_utilisateur = ev.id_evaluateur', 'left')
            ->join('entretien e', 'e.id_entretien = ev.id_entretien', 'left')
            ->join('test_technique t', 't.id_test = ev.id_test', 'left')
            ->where('ev.id_candidature', $idCandidature)
            ->orderBy('ev.date_evaluation', 'DESC')
            ->get()->getResultArray();
    }

    /** Historique des changements de statut. */
    public function historiqueDe(int $idCandidature): array
    {
        return $this->db->table('historique_candidature h')
            ->select('h.*, u.email AS auteur')
            ->join('utilisateur u', 'u.id_utilisateur = h.id_utilisateur', 'left')
            ->where('h.id_candidature', $idCandidature)
            ->orderBy('h.date_action', 'DESC')
            ->get()->getResultArray();
    }

    public function documentsEncodees(int $idCandidature): ?array
    {
        $row = $this->db->table('candidature')
            ->select('cv_donnees, cv_type_mime, cv_nom, lettre_donnees, lettre_type_mime, lettre_nom, cin_donnees, cin_type_mime, cin_nom')
            ->where('id_candidature', $idCandidature)
            ->get()->getRowArray();
        if (! $row) {
            return null;
        }
        $row['cv_hex'] = bin2hex((string) $row['cv_donnees']);
        $row['lettre_hex'] = bin2hex((string) $row['lettre_donnees']);
        $row['cin_hex'] = bin2hex((string) $row['cin_donnees']);
        unset($row['cv_donnees'], $row['lettre_donnees'], $row['cin_donnees']);
        return $row;
    }

        /** Toutes les offres auxquelles ce candidat a déjà postulé. */
    public function offresDejaPostulees(int $idCandidat): array
    {
        $rows = $this->db->table('candidature')
            ->select('id_offre')
            ->where('id_candidat', $idCandidat)
            ->get()->getResultArray();
        return array_map('intval', array_column($rows, 'id_offre'));
    }

    /** Retourne le id_candidat pour un id_utilisateur, ou null. */
    public function idCandidatPourUtilisateur(int $idUtilisateur): ?int
    {
        $row = $this->db->table('candidat c')
            ->select('c.id_candidat')
            ->join('personne p', 'p.id_personne = c.id_personne')
            ->where('p.id_utilisateur', $idUtilisateur)
            ->get()->getRowArray();
        return $row ? (int) $row['id_candidat'] : null;
    }

    /** Liste des candidatures d'un candidat (pour la page Mes candidatures). */
    public function listeParCandidat(int $idCandidat): array
    {
        return $this->db->table('candidature c')
            ->select('c.id_candidature, c.statut, c.date_candidature,
                    o.titre AS offre_titre, o.statut AS offre_statut, o.date_limite')
            ->join('offre_emploi o', 'o.id_offre = c.id_offre', 'left')
            ->where('c.id_candidat', $idCandidat)
            ->orderBy('c.date_candidature', 'DESC')
            ->get()->getResultArray();
    }
    
    public function preselectionnees(): array
    {
        return $this->db->query(
            "SELECT c.id_candidature, c.statut, c.date_candidature, p.nom, p.prenom, o.titre
             FROM candidature c
             JOIN candidat ca ON ca.id_candidat = c.id_candidat
             JOIN personne p  ON p.id_personne = ca.id_personne
             JOIN offre_emploi o ON o.id_offre = c.id_offre
             WHERE c.statut IN ('PRESELECTIONNEE','SELECTIONNEE','REFUSEE')
             ORDER BY c.date_candidature DESC"
        )->getResultArray();
    }

    public function infos(int $id): ?array
    {
        return $this->db->query(
            "SELECT c.id_candidature, c.statut, p.nom, p.prenom, p.telephone, u.email, o.titre
             FROM candidature c
             JOIN candidat ca ON ca.id_candidat = c.id_candidat
             JOIN personne p  ON p.id_personne = ca.id_personne
             JOIN utilisateur u ON u.id_utilisateur = p.id_utilisateur
             JOIN offre_emploi o ON o.id_offre = c.id_offre
             WHERE c.id_candidature = ?", [$id]
        )->getRowArray() ?: null;
    }

    /** RG-EVAL-010 : PRESELECTIONNEE → SELECTIONNEE | REFUSEE, historisé. */
    public function decider(int $id, string $decision, int $userId, ?string $comm = null): void
    {
        if (! in_array($decision, ['SELECTIONNEE', 'REFUSEE'], true)) {
            throw new DomainException('Décision invalide.');
        }
        $this->db->transBegin();
        try {
            $row = $this->db->query('SELECT statut FROM candidature WHERE id_candidature = ?', [$id])->getRowArray();
            if (! $row || $row['statut'] !== 'PRESELECTIONNEE') {
                throw new DomainException('Candidature non présélectionnée.');
            }
            $this->db->table('candidature')->where('id_candidature', $id)->update(['statut' => $decision]);
            $this->db->table('historique_candidature')->insert([
                'id_candidature' => $id, 'ancien_statut' => $row['statut'], 'nouveau_statut' => $decision,
                'id_utilisateur' => $userId, 'commentaire' => $comm,
            ]);
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

}
