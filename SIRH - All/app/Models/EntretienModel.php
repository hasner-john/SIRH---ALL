<?php
namespace App\Models;

use Throwable;

class EntretienModel extends BaseStatutModel
{
    protected $table            = 'entretien';
    protected $primaryKey       = 'id_entretien';
    protected string $historiqueTable = 'historique_entretien';
    protected string $historiqueForeignKey = 'id_entretien';

    public function liste(): array
    {
        return $this->db->query(
            "SELECT e.id_entretien, e.id_candidature, e.date_entretien, e.lieu, e.statut,
                    p.nom, p.prenom, o.titre
             FROM entretien e
             JOIN candidature c ON c.id_candidature = e.id_candidature
             JOIN candidat ca   ON ca.id_candidat = c.id_candidat
             JOIN personne p    ON p.id_personne = ca.id_personne
             JOIN offre_emploi o ON o.id_offre = c.id_offre
             ORDER BY e.date_entretien DESC"
        )->getResultArray();
    }

    public function planifier(int $idCand, int $idOrg, string $date, ?string $lieu, ?string $notes): int
    {
        $this->db->transBegin();
        try {
            $this->exigerPreselection($idCand);
            $this->db->table('entretien')->insert([
                'id_candidature'  => $idCand,
                'id_organisateur' => $idOrg,
                'date_entretien'  => $date,
                'lieu'            => $lieu,
                'notes'           => $notes,
            ]);
            $id = (int) $this->db->insertID();
            $this->hist($id, null, 'PLANIFIE', $idOrg, 'Création'); // RG-HIST-002
            $this->db->transCommit();
            return $id;
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    public function deCandidature(int $idCand): array
    {
        return $this->db->table('entretien')->where('id_candidature', $idCand)->orderBy('date_entretien')->get()->getResultArray();
    }
}
