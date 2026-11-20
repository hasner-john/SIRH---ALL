<?php

namespace App\Models;

use CodeIgniter\Model;

class OffreEmploiModel extends Model
{
    protected $table = 'offre_emploi';
    protected $primaryKey = 'id_offre';
    protected $returnType = 'array';

    protected $allowedFields = [
        'id_demande',
        'titre',
        'description',
        'date_publication',
        'date_limite',
        'statut',
        'cree_par'
    ];

    protected $useTimestamps = false;

    /**
     * Demandes validées qui n'ont pas encore d'offre.
     */
    public function getDemandesEligibles()
    {
        return $this->db->table('demande_recrutement dr')
            ->select('
                dr.id_demande,
                dr.effectif_demande,
                dr.motif,
                dr.salaire_par_personne,
                dr.date_besoin,
                p.libelle AS poste,
                p.description AS description_poste,
                d.nom AS departement
            ')
            ->join('poste p', 'p.id_poste = dr.id_poste')
            ->join('departement d', 'd.id_departement = p.id_departement')
            ->join(
                'offre_emploi o',
                'o.id_demande = dr.id_demande',
                'left'
            )
            ->where('dr.statut', 'VALIDE')
            ->where('o.id_offre', null)
            ->where('p.statut', 'ACTIF')
            ->orderBy('dr.date_creation', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Récupérer une demande validée avec ses détails.
     */
    public function getDemandeValidee($idDemande)
    {
        return $this->db->table('demande_recrutement dr')
            ->select('
                dr.*,
                p.libelle AS poste,
                p.description AS description_poste,
                d.nom AS departement
            ')
            ->join('poste p', 'p.id_poste = dr.id_poste')
            ->join('departement d', 'd.id_departement = p.id_departement')
            ->where('dr.id_demande', $idDemande)
            ->where('dr.statut', 'VALIDE')
            ->where('p.statut', 'ACTIF')
            ->get()
            ->getRowArray();
    }

    /**
     * Récupérer une offre avec les informations de sa demande.
     */
    public function getOffreAvecDetails($idOffre)
    {
        return $this->db->table('offre_emploi o')
            ->select('
                o.*,
                dr.id_poste,
                dr.effectif_demande,
                dr.motif,
                dr.salaire_par_personne,
                dr.date_besoin,
                p.libelle AS poste,
                d.nom AS departement
            ')
            ->join(
                'demande_recrutement dr',
                'dr.id_demande = o.id_demande'
            )
            ->join('poste p', 'p.id_poste = dr.id_poste')
            ->join('departement d', 'd.id_departement = p.id_departement')
            ->where('o.id_offre', $idOffre)
            ->get()
            ->getRowArray();
    }

    /**
     * Lister les offres effectivement publiées et non clôturées.
     */
    public function getOffresPubliees()
    {
        return $this->db->table('offre_emploi o')
            ->select('
                o.*,
                p.libelle AS poste,
                d.nom AS departement
            ')
            ->join(
                'demande_recrutement dr',
                'dr.id_demande = o.id_demande'
            )
            ->join('poste p', 'p.id_poste = dr.id_poste')
            ->join('departement d', 'd.id_departement = p.id_departement')
            ->where('o.statut', 'PUBLIEE')
            ->where('o.date_publication <=', date('Y-m-d'))
            ->groupStart()
                ->where('o.date_limite >=', date('Y-m-d'))
                ->orWhere('o.date_limite', null)
            ->groupEnd()
            ->orderBy('o.date_publication', 'DESC')
            ->get()
            ->getResultArray();
    }
}
