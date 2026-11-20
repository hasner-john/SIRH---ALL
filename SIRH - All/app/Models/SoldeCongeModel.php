<?php
namespace App\Models;
use CodeIgniter\Model;

class SoldeCongeModel extends Model
{
    protected $table = 'solde_conge';
    protected $primaryKey = 'id_solde_conge';
    protected $returnType = 'array';
    protected $allowedFields = ['id_employe', 'id_type_conge', 'annee', 'acquis', 'pris', 'ajuste'];
    protected $useTimestamps = false;

    public function pourEmploye(int $employe, ?int $annee = null): array
    {
        return $this->select('solde_conge.*, type_conge.libelle AS type_libelle')
            ->join('type_conge', 'type_conge.id_type_conge = solde_conge.id_type_conge')
            ->where('id_employe', $employe)->where('annee', $annee ?? (int) date('Y'))
            ->findAll();
    }
}
