<?php

declare(strict_types=1);

namespace App\Controllers\Rh;

use App\Libraries\Audit;

class CompetencesController extends RhController
{
    public function index()
    {
        $postes = $this->db->table('poste')->orderBy('libelle')->get()->getResultArray();
        $matrice = $this->db->table('poste_competence pc')->select('pc.*, c.code, c.libelle')
            ->join('competence c', 'c.id_competence = pc.id_competence')->orderBy('c.code')->get()->getResultArray();
        $parPoste = [];
        foreach ($matrice as $m) {
            $parPoste[$m['id_poste']][] = $m;
        }
        return view('rh/competences', [
            'titre' => 'Compétences', 'competences' => $this->db->table('competence')->orderBy('code')->get()->getResultArray(),
            'postes' => $postes, 'parPoste' => $parPoste,
        ]);
    }

    /** Création / modification d'une compétence (code unique). */
    public function enregistrer()
    {
        $id = (int) $this->request->getPost('id_competence');
        $d = [
            'code' => strtoupper(trim((string) $this->request->getPost('code'))), 'libelle' => trim((string) $this->request->getPost('libelle')),
            'description' => trim((string) $this->request->getPost('description')), 'actif' => (int) (bool) $this->request->getPost('actif'),
        ];
        if ($d['code'] === '' || $d['libelle'] === '') {
            return $this->ko('Code et libellé requis.');
        }
        $doublon = $this->db->table('competence')->where('code', $d['code'])->where('id_competence !=', $id)->countAllResults();
        if ($doublon > 0) {
            return $this->ko('Ce code existe déjà.');
        }
        if ($id > 0) {
            $this->db->table('competence')->where('id_competence', $id)->update($d);
            Audit::log('UPDATE', 'competence', $id, null, $d);
        } else {
            $this->db->table('competence')->insert($d);
            Audit::log('CREATE', 'competence', (int) $this->db->insertID(), null, $d);
        }
        return $this->ok('Compétence enregistrée.');
    }

    /** Ajoute/retire une compétence d'un poste (action = lier|retirer). */
    public function lierPoste()
    {
        $idP = (int) $this->request->getPost('id_poste');
        $idC = (int) $this->request->getPost('id_competence');
        if ((string) $this->request->getPost('action') === 'retirer') {
            $this->db->table('poste_competence')->where(['id_poste' => $idP, 'id_competence' => $idC])->delete();
            Audit::log('DELETE', 'poste_competence', null, ['poste' => $idP, 'competence' => $idC]);
            return $this->ok('Compétence retirée du poste.');
        }
        $niv = (int) $this->request->getPost('niveau_attendu');
        if ($idP <= 0 || $idC <= 0 || $niv < 1 || $niv > 5) {
            return $this->ko('Poste, compétence et niveau (1 à 5) requis.');
        }
        $this->db->query('INSERT INTO poste_competence (id_poste, id_competence, niveau_attendu) VALUES (?,?,?) ON CONFLICT(id_poste, id_competence) DO UPDATE SET niveau_attendu = excluded.niveau_attendu', [$idP, $idC, $niv]);
        Audit::log('UPDATE', 'poste_competence', null, null, ['poste' => $idP, 'competence' => $idC, 'niveau' => $niv]);
        return $this->ok('Matrice mise à jour.');
    }
}
