<?php
namespace App\Models;

use Throwable;

class TestTechniqueModel extends BaseStatutModel
{
    protected $table            = 'test_technique';
    protected $primaryKey       = 'id_test';
    protected string $historiqueTable = 'historique_test_technique';
    protected string $historiqueForeignKey = 'id_test';

    public function liste(): array
    {
        return $this->db->query(
            "SELECT t.id_test, t.id_candidature, t.intitule, t.date_test, t.statut,
                    p.nom, p.prenom, o.titre
             FROM test_technique t
             JOIN candidature c ON c.id_candidature = t.id_candidature
             JOIN candidat ca   ON ca.id_candidat = c.id_candidat
             JOIN personne p    ON p.id_personne = ca.id_personne
             JOIN offre_emploi o ON o.id_offre = c.id_offre
             ORDER BY t.date_test DESC"
        )->getResultArray();
    }

    public function planifier(int $idCand, int $idOrg, string $intitule, ?string $desc, string $date): int
    {
        $this->db->transBegin();
        try {
            $this->exigerPreselection($idCand);
            $this->db->table('test_technique')->insert([
                'id_candidature'  => $idCand,
                'id_organisateur' => $idOrg,
                'intitule'        => $intitule,
                'description'     => $desc,
                'date_test'       => $date,
            ]);
            $id = (int) $this->db->insertID();
            $this->hist($id, null, 'PLANIFIE', $idOrg, 'Création');
            $this->db->transCommit();
            return $id;
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    public function deCandidature(int $idCand): array
    {
        return $this->db->table('test_technique')->where('id_candidature', $idCand)->orderBy('date_test')->get()->getResultArray();
    }
}
