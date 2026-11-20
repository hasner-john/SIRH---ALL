<?php
namespace App\Models;

use CodeIgniter\Model;
use DomainException;
use Throwable;

/**
 * Base commune : changement de statut + historique dans UNE transaction.
 * (RG-HIST-001, RG-HIST-004)
 */
abstract class BaseStatutModel extends Model
{
    protected $returnType = 'array';
    protected string $historiqueTable;
    protected array $transitions = ['PLANIFIE' => ['REALISE', 'ANNULE']]; // RG-EVAL-002

    protected function hist(int $id, ?string $ancien, string $nouveau, int $userId, ?string $comm): void
    {
        $this->db->table($this->historiqueTable)->insert([
            $this->historiqueForeignKey => $id,
            'ancien_statut'   => $ancien,
            'nouveau_statut'  => $nouveau,
            'id_utilisateur'  => $userId,
            'commentaire'     => $comm,
        ]);
    }

    protected string $historiqueForeignKey;

    /** La candidature doit être PRESELECTIONNEE (RG-CAND-008). À appeler dans une transaction. */
    protected function exigerPreselection(int $idCand): void
    {
        $row = $this->db->query(
            'SELECT statut FROM candidature WHERE id_candidature = ?', [$idCand]
        )->getRowArray();
        if (! $row || $row['statut'] !== 'PRESELECTIONNEE') {
            throw new DomainException('La candidature doit être PRESELECTIONNEE.');
        }
    }

    public function changerStatut(int $id, string $nouveau, int $userId, ?string $comm = null): void
    {
        $this->db->transBegin();
        try {
            $row = $this->db->query(
                "SELECT statut FROM {$this->table} WHERE {$this->primaryKey} = ?", [$id]
            )->getRowArray();
            if (! $row) {
                throw new DomainException('Enregistrement introuvable.');
            }
            $ancien = $row['statut'];
            if (! in_array($nouveau, $this->transitions[$ancien] ?? [], true)) {
                throw new DomainException("Transition $ancien → $nouveau interdite.");
            }
            $this->db->table($this->table)->where($this->primaryKey, $id)->update(['statut' => $nouveau]);
            $this->hist($id, $ancien, $nouveau, $userId, $comm);
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}
