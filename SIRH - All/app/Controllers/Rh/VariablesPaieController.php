<?php

declare(strict_types=1);

namespace App\Controllers\Rh;

use App\Libraries\Audit;

class VariablesPaieController extends RhController
{
    public function index()
    {
        $q = $this->db->table('variable_paie v')
            ->select('v.*, e.matricule, pe.nom, pe.prenom, r.libelle AS rubrique, r.categorie, p.mois, p.annee, p.statut AS statut_periode')
            ->join('employe e', 'e.id_employe = v.id_employe')->join('personne pe', 'pe.id_personne = e.id_personne')
            ->join('rubrique_paie r', 'r.id_rubrique = v.id_rubrique')->join('periode_paie p', 'p.id_periode = v.id_periode');
        foreach (['id_periode' => 'v.id_periode', 'id_employe' => 'v.id_employe'] as $get => $col) {
            if ((int) $this->request->getGet($get) > 0) {
                $q->where($col, (int) $this->request->getGet($get));
            }
        }
        if ($this->request->getGet('categorie')) {
            $q->where('r.categorie', (string) $this->request->getGet('categorie'));
        }
        return view('rh/paie/variables', [
            'titre' => 'Variables de paie',
            'variables' => $q->orderBy('p.annee', 'DESC')->orderBy('p.mois', 'DESC')->get()->getResultArray(),
            'employes' => $this->employes(),
            'periodes' => $this->db->table('periode_paie')->whereIn('statut', ['OUVERT', 'CALCUL'])->orderBy('annee', 'DESC')->orderBy('mois', 'DESC')->get()->getResultArray(),
            'toutesPeriodes' => $this->db->table('periode_paie')->orderBy('annee', 'DESC')->orderBy('mois', 'DESC')->get()->getResultArray(),
            'rubriques' => $this->db->table('rubrique_paie')->whereIn('categorie', ['INDEMNITE', 'PRIME', 'RAPPEL', 'AVANCE'])->where('actif', 1)->orderBy('categorie')->get()->getResultArray(),
            'filtre' => $this->request->getGet(),
        ]);
    }

    /** Upsert : (employé, période, rubrique) unique. */
    public function enregistrer()
    {
        $idE = (int) $this->request->getPost('id_employe');
        $idP = (int) $this->request->getPost('id_periode');
        $idR = (int) $this->request->getPost('id_rubrique');
        $montant = (float) $this->request->getPost('montant');
        $periode = $this->db->table('periode_paie')->where('id_periode', $idP)->get()->getRowArray();
        $rub = $this->db->table('rubrique_paie')->where('id_rubrique', $idR)->get()->getRowArray();
        if (! $periode || ! $rub || $idE <= 0) {
            return $this->ko('Employé, période et rubrique requis.');
        }
        if (! in_array($periode['statut'], ['OUVERT', 'CALCUL'], true)) {
            return $this->ko('Période verrouillée : utilisez une variable RAPPEL sur la période suivante.');
        }
        if ($rub['categorie'] === 'HS' || ! in_array($rub['categorie'], ['INDEMNITE', 'PRIME', 'RAPPEL', 'AVANCE'], true)) {
            return $this->ko('Rubrique non saisissable (les heures supplémentaires viennent de leur page).');
        }
        if ($montant < 0) {
            return $this->ko('Montant négatif interdit.');
        }
        $data = ['nombre' => (float) $this->request->getPost('nombre') ?: 1, 'montant' => $montant, 'motif' => trim((string) $this->request->getPost('motif'))];
        $existe = $this->db->table('variable_paie')->where(['id_employe' => $idE, 'id_periode' => $idP, 'id_rubrique' => $idR])->get()->getRowArray();
        if ($existe) {
            $this->db->table('variable_paie')->where('id_variable', $existe['id_variable'])->update($data);
            Audit::log('UPDATE', 'variable_paie', (int) $existe['id_variable'], ['montant' => $existe['montant']], ['montant' => $montant], $data['motif']);
            $msg = 'Variable modifiée.';
            $id = (int) $existe['id_variable'];
        } else {
            $this->db->table('variable_paie')->insert(['id_employe' => $idE, 'id_periode' => $idP, 'id_rubrique' => $idR] + $data);
            $id = (int) $this->db->insertID();
            Audit::log('CREATE', 'variable_paie', $id, null, ['montant' => $montant], $data['motif']);
            $msg = 'Variable enregistrée.';
        }
        if ($periode['statut'] === 'CALCUL') {
            $msg .= ' Recalcul nécessaire pour la période.';
        }
        return $this->ok($msg);
    }

    public function supprimer(int $id)
    {
        $v = $this->db->table('variable_paie v')->select('v.*, p.statut AS sp')->join('periode_paie p', 'p.id_periode = v.id_periode')->where('v.id_variable', $id)->get()->getRowArray();
        if (! $v) {
            return $this->ko('Variable introuvable.');
        }
        if (! in_array($v['sp'], ['OUVERT', 'CALCUL'], true)) {
            return $this->ko('Période verrouillée : suppression interdite.');
        }
        $this->db->table('variable_paie')->where('id_variable', $id)->delete();
        Audit::log('DELETE', 'variable_paie', $id, ['montant' => $v['montant']], null, 'Suppression');
        return $this->ok('Variable supprimée.' . ($v['sp'] === 'CALCUL' ? ' Recalcul nécessaire.' : ''));
    }
}
