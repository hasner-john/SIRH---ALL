<?php

namespace App\Models;

use CodeIgniter\Model;

class EmployeModel extends Model
{
    protected $table = 'employe';
    protected $primaryKey = 'id_employe';
    protected $returnType = 'array';
    protected $allowedFields = [
        'id_contrat', 'id_personne', 'matricule', 'date_embauche', 'statut',
    ];
    protected $useTimestamps = false;

    public function listeDossiers(): array
    {
        return $this->db->table('employe e')
            ->select('e.*, pe.nom, pe.prenom, pe.telephone, u.email,
                      c.reference, c.type_contrat, c.salaire, c.periodicite_salaire,
                      af.fonction, d.nom AS departement_nom, po.libelle AS poste_libelle')
            ->join('personne pe', 'pe.id_personne = e.id_personne')
            ->join('utilisateur u', 'u.id_utilisateur = pe.id_utilisateur')
            ->join('contrat c', 'c.id_contrat = e.id_contrat')
            ->join('affectation af', 'af.id_employe = e.id_employe AND af.date_fin IS NULL', 'left')
            ->join('departement d', 'd.id_departement = af.id_departement', 'left')
            ->join('poste po', 'po.libelle = af.fonction', 'left')
            ->orderBy('pe.nom', 'ASC')
            ->get()->getResultArray();
    }

    public function dossier(int $idEmploye): ?array
    {
        return $this->db->table('employe e')
            ->select('e.*, pe.id_personne, pe.nom, pe.prenom, pe.telephone, pe.adresse,
                      pe.date_naissance, u.email, c.id_contrat, c.reference,
                      c.type_contrat, c.date_debut, c.date_fin, c.salaire,
                      c.periodicite_salaire, c.statut AS contrat_statut,
                      c.lieu_travail, c.missions, c.horaires, c.conges_absences')
            ->join('personne pe', 'pe.id_personne = e.id_personne')
            ->join('utilisateur u', 'u.id_utilisateur = pe.id_utilisateur')
            ->join('contrat c', 'c.id_contrat = e.id_contrat')
            ->where('e.id_employe', $idEmploye)
            ->get()->getRowArray();
    }

    public function affectations(int $idEmploye): array
    {
        return $this->db->table('affectation a')
            ->select('a.*, d.nom AS departement_nom')
            ->join('departement d', 'd.id_departement = a.id_departement')
            ->where('a.id_employe', $idEmploye)
            ->orderBy('a.date_debut', 'DESC')
            ->get()->getResultArray();
    }

    public function salaires(int $idEmploye): array
    {
        return $this->db->table('historique_salaire')
            ->where('id_employe', $idEmploye)
            ->orderBy('date_effet', 'DESC')
            ->get()->getResultArray();
    }

    public function documents(int $idEmploye): array
    {
        return $this->db->table('document_employe')
            ->select('id_document, type_document, nom_fichier, type_mime,
                      date_expiration, commentaire, date_creation, date_modification')
            ->where('id_employe', $idEmploye)
            ->orderBy('date_expiration', 'ASC')
            ->get()->getResultArray();
    }
}
