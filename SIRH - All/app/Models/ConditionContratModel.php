<?php

namespace App\Models;

use CodeIgniter\Model;

class ConditionContratModel extends Model
{
    protected $table = 'condition_contrat';
    protected $primaryKey = 'id_condition';
    protected $returnType = 'array';

    protected $allowedFields = [
        'id_poste',
        'libelle',
        'type_valeur',
        'valeur_condition',
        'ordre_affichage',
        'statut',
        'modifie_par',
        'date_modification',
    ];

    protected $useTimestamps = false;

    
    public function forPoste(int $postId): array
    {
        return $this->where('id_poste', $postId)
            ->orderBy('ordre_affichage', 'ASC')
            ->orderBy('id_condition', 'ASC')
            ->findAll();
    }
    
    public function getPostesAvecNombreConditions(): array
    {
        return $this->db->table('poste p')
            ->select('p.id_poste, p.libelle, p.statut, d.nom AS departement,
                      COUNT(c.id_condition) AS nombre_conditions')
            ->join('departement d', 'd.id_departement = p.id_departement')
            ->join(
                'condition_contrat c',
                "c.id_poste = p.id_poste AND c.statut != 'INACTIF'",
                'left'
            )
            ->groupBy('p.id_poste, p.libelle, p.statut, d.nom')
            ->orderBy('p.libelle', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getConditionsParPoste(int $idPoste): array
    {
        return $this->where('id_poste', $idPoste)
            ->orderBy('ordre_affichage', 'ASC')
            ->findAll();
    }

    public function conditionExiste(int $idCondition): bool
    {
        return $this->find($idCondition) !== null;
    }

    /**
     * Vérifie si la condition correspond à une copie
     * intégrée dans un contrat signé.
     */
    public function estDansUnContratSigne(array $condition): bool
    {
        return $this->db->table('contrat_condition cc')
            ->join('contrat ct', 'ct.id_contrat = cc.id_contrat')
            ->join('candidature ca', 'ca.id_candidature = ct.id_candidature')
            ->join('offre_emploi oe', 'oe.id_offre = ca.id_offre')
            ->join('demande_recrutement dr', 'dr.id_demande = oe.id_demande')
            ->where('ct.statut', 'SIGNE')
            ->where('dr.id_poste', $condition['id_poste'])
            ->where('cc.libelle', $condition['libelle'])
            ->where('cc.type_valeur', $condition['type_valeur'])
            ->where('cc.valeur_finale', $condition['valeur_condition'])
            ->where('cc.ordre_affichage', $condition['ordre_affichage'])
            ->countAllResults() > 0;
    }

    public function getPoste(int $idPoste): ?array
    {
        $poste = $this->db->table('poste p')
            ->select('p.id_poste, p.libelle, p.statut, d.nom AS departement')
            ->join('departement d', 'd.id_departement = p.id_departement')
            ->where('p.id_poste', $idPoste)
            ->get()
            ->getRowArray();

        return $poste ?: null;
    }
}
