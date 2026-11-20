<?php

namespace App\Controllers\Direction;

use App\Controllers\BaseController;
use App\Models\DemandeModel;

class DemandesController extends BaseController
{
    private const STATUTS = ['EN_ATTENTE', 'VALIDE', 'REFUSEE', 'ANNULEE'];

    public function index()
    {
        $model  = new DemandeModel();
        $statut = $this->request->getGet('statut');
        if (! in_array($statut, self::STATUTS, true)) {
            $statut = null;
        }

        return view('direction/demandes/index', [
            'title'    => 'Demandes de recrutement',
            'demandes' => $model->getDemandesAvecDetails($statut),
            'comptes'  => $model->compterParStatut(),
            'statut'   => $statut,
            'statuts'  => self::STATUTS,
        ]);
    }

    public function show($id)
    {
        $id      = (int) $id;
        $demande = (new DemandeModel())->getDemandeAvecDetails($id);

        if (! $demande || $demande['statut'] === 'BROUILLON') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Demande $id introuvable");
        }

        $db = \Config\Database::connect();

        $historiques = $db->table('historique_demande h')
            ->select('h.*, u.email AS auteur')
            ->join('utilisateur u', 'u.id_utilisateur = h.id_utilisateur', 'left')
            ->where('h.id_demande', $id)
            ->orderBy('h.date_action', 'DESC')
            ->get()->getResultArray();

        $validations = $db->table('validation_demande v')
            ->select('v.*, u.email AS validateur')
            ->join('utilisateur u', 'u.id_utilisateur = v.id_validateur', 'left')
            ->where('v.id_demande', $id)
            ->orderBy('v.date_decision', 'DESC')
            ->get()->getResultArray();

        return view('direction/demandes/show', [
            'title'       => 'Demande : ' . $demande['poste'],
            'demande'     => $demande,
            'historiques' => $historiques,
            'validations' => $validations,
        ]);
    }

    public function valider($id) { return $this->decider((int) $id, 'VALIDE'); }
    public function refuser($id) { return $this->decider((int) $id, 'REFUSEE'); }

    private function decider(int $id, string $decision)
    {
        // Même si la route autorise FINANCE, seule DIRECTION décide
        if (session()->get('role') !== 'DIRECTION') {
            return redirect()->back()->with('error', 'Seule la Direction peut prendre cette décision.');
        }

        $demande = (new DemandeModel())->find($id);
        if (! $demande) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        if ($demande['statut'] !== 'EN_ATTENTE') {
            return redirect()->back()->with('error', "Cette demande n'est pas en attente (statut actuel : {$demande['statut']}).");
        }

        $idUtilisateur = (int) session()->get('id_utilisateur');
        $commentaire   = trim((string) ($this->request->getPost('commentaire') ?? ''));

        // Un refus doit être motivé
        if ($decision === 'REFUSEE' && $commentaire === '') {
            return redirect()->back()->with('error', 'Veuillez indiquer le motif du refus.');
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $db->table('demande_recrutement')->where('id_demande', $id)->update([
                'statut'            => $decision,
                'date_modification' => date('Y-m-d H:i:s'),
            ]);

            $db->table('validation_demande')->insert([
                'id_demande'      => $id,
                'id_validateur'   => $idUtilisateur,
                'type_validation' => 'DIRECTION',
                'decision'        => $decision,
                'commentaire'     => $commentaire ?: null,
            ]);

            $db->table('historique_demande')->insert([
                'id_demande'     => $id,
                'ancien_statut'  => 'EN_ATTENTE',
                'nouveau_statut' => $decision,
                'id_utilisateur' => $idUtilisateur,
                'commentaire'    => $commentaire ?: ('Décision Direction : ' . $decision),
            ]);

            $db->transCommit();
            return redirect()->to('/direction/demandes')->with('success', "Demande $decision.");
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Direction\DemandesController::decider → ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la décision.');
        }
    }
}