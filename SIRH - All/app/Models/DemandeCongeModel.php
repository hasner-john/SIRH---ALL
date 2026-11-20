<?php
namespace App\Models;
use CodeIgniter\Model;

class DemandeCongeModel extends Model
{
    protected $table = 'demande_conge';
    protected $primaryKey = 'id_demande_conge';
    protected $returnType = 'array';
    protected $allowedFields = ['id_employe', 'id_type_conge', 'date_debut', 'date_fin', 'nombre_jours', 'motif', 'statut', 'id_validateur', 'commentaire_validation', 'date_decision'];
    protected $useTimestamps = false;

    public function avecDetails(): array
    {
        return $this->select('demande_conge.*, type_conge.libelle AS type_libelle, e.matricule, p.nom, p.prenom')
            ->join('type_conge', 'type_conge.id_type_conge = demande_conge.id_type_conge')
            ->join('employe e', 'e.id_employe = demande_conge.id_employe')
            ->join('personne p', 'p.id_personne = e.id_personne')
            ->orderBy('date_creation', 'DESC')->findAll();
    }
}
