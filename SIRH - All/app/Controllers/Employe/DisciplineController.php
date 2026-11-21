<?php

declare(strict_types=1);

namespace App\Controllers\Employe;

use App\Libraries\Audit;
use App\Services\DisciplineService;

class DisciplineController extends EmployeBaseController
{
    public function index()
    {
        $idE = (int) ($this->emp['id_employe'] ?? 0);
        $incidents = $this->db->table('incident i')->select('i.*, d.texte AS demande, d.date_limite, r.texte AS reponse, r.date_reponse, r.en_retard, s.id_sanction, s.type_sanction, s.statut AS statut_sanction, s.date_effet, s.nb_jours, s.motif AS motif_sanction')
            ->join('demande_explication d', 'd.id_incident = i.id_incident', 'left')->join('reponse_explication r', 'r.id_incident = i.id_incident', 'left')
            ->join('sanction s', 's.id_incident = i.id_incident', 'left')->where('i.id_employe', $idE)->whereNotIn('i.statut', ['SIGNALE'])->orderBy('i.date_incident', 'DESC')->get()->getResultArray();
        if ($idE) {
            Audit::acces('CONSULTATION', 'DOSSIER_DISCIPLINAIRE', $idE, 'Consultation par l’employé');
        }
        return $this->vue('employe/discipline', ['titre' => 'Ma discipline', 'incidents' => $incidents]);
    }

    public function repondre(int $idIncident)
    {
        $inc = $this->db->table('incident')->where(['id_incident' => $idIncident, 'id_employe' => (int) ($this->emp['id_employe'] ?? 0)])->get()->getRowArray();
        if (! $inc) {
            return redirect()->back()->with('error', 'Incident introuvable.');
        }
        try {
            (new DisciplineService())->enregistrerReponse($idIncident, (string) $this->request->getPost('texte'), date('Y-m-d'), $this->request->getFile('piece'), $this->uid());
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
        return redirect()->back()->with('success', 'Votre réponse a été transmise.');
    }

    public function contester(int $idSanction)
    {
        $s = $this->db->table('sanction s')->select('s.id_sanction')->join('incident i', 'i.id_incident = s.id_incident')
            ->where(['s.id_sanction' => $idSanction, 'i.id_employe' => (int) ($this->emp['id_employe'] ?? 0)])->get()->getRowArray();
        $com = trim((string) $this->request->getPost('commentaire'));
        if (! $s || $com === '') {
            return redirect()->back()->with('error', 'Sanction introuvable ou commentaire manquant.');
        }
        try {
            (new DisciplineService())->statutSanction($idSanction, 'CONTESTEE', $com);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
        return redirect()->back()->with('success', 'Contestation enregistrée.');
    }
}
