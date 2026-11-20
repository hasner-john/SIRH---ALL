<?php

namespace App\Controllers\Rh;

use App\Controllers\BaseController;
use App\Models\CandidatureModel;
use App\Services\CandidatureService;

class CandidaturesController extends BaseController
{
    protected $candidatureModel;
    protected $candidatureService;

    public function __construct()
    {
        $this->candidatureModel = new CandidatureModel();
        $this->candidatureService = new CandidatureService();
    }

    /**
     * Afficher les candidatures d'une offre.
     */
    public function index($idOffre)
    {
        $candidatures = $this->candidatureModel
                             ->getCandidaturesParOffre($idOffre);

        return view('rh/candidatures/liste', [
            'idOffre' => $idOffre,
            'candidatures' => $candidatures
        ]);
    }

    /**
     * Afficher les détails d'une candidature et du candidat.
     */
    public function detail($idCandidature)
    {
        $db = \Config\Database::connect();

        $candidature = $db->table('candidature ca')
            ->select('ca.*, p.nom, p.prenom, p.telephone, u.email')
            ->join('candidat c', 'c.id_candidat = ca.id_candidat')
            ->join('personne p', 'p.id_personne = c.id_personne')
            ->join('utilisateur u', 'u.id_utilisateur = p.id_utilisateur')
            ->where('ca.id_candidature', $idCandidature)
            ->get()
            ->getRowArray();

        if (!$candidature) {
            return redirect()->back()
                ->with('erreur', 'Candidature introuvable.');
        }

        return view('rh/candidatures/detail', [
            'candidature' => $candidature
        ]);
    }

    /**
     * Consulter un document sans exposer son chemin au public.
     */
    public function document($idCandidature, $type)
    {
        $documents = [
            'cv' => ['cv_donnees', 'cv_type_mime', 'cv_nom'],
            'lettre' => ['lettre_donnees', 'lettre_type_mime', 'lettre_nom'],
            'lettre_motivation' => ['lettre_donnees', 'lettre_type_mime', 'lettre_nom'],
            'cin' => ['cin_donnees', 'cin_type_mime', 'cin_nom'],
        ];
        if (! isset($documents[$type])) {
            return $this->response
                ->setStatusCode(400)
                ->setBody('Type de document invalide.');
        }

        $db = \Config\Database::connect();
        $document = $db->table('candidature')
            ->select(implode(', ', $documents[$type]))
            ->where('id_candidature', (int) $idCandidature)
            ->get()->getRowArray();
        if (! $document) {
            return $this->response
                ->setStatusCode(404)
                ->setBody('Candidature introuvable.');
        }

        [$dataColumn, $mimeColumn, $nameColumn] = $documents[$type];
        if ($document[$dataColumn] === null || $document[$dataColumn] === '') {
            return $this->response
                ->setStatusCode(404)
                ->setBody('Document absent.');
        }

        return $this->response
            ->setHeader('Content-Type', $document[$mimeColumn] ?: 'application/octet-stream')
            ->setHeader(
                'Content-Disposition',
                'inline; filename="' . addslashes($document[$nameColumn] ?: ($type . '.bin')) . '"'
            )
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody($document[$dataColumn]);
    }

    /**
     * Enregistrer la décision RH.
     */
    public function decider($idCandidature)
    {
        $statut = $this->request->getPost('statut');
        $commentaire = $this->request->getPost('commentaire');

        $idUtilisateur = session()->get('id_utilisateur');

        if (!$idUtilisateur) {
            return redirect()->to('/connexion')
                ->with('erreur', 'Veuillez vous connecter.');
        }

        try {
            $this->candidatureService->deciderCandidature(
                $idCandidature,
                $statut,
                $idUtilisateur,
                $commentaire
            );

            if ($statut === 'PRESELECTIONNEE') {
                return redirect()->to(
                    '/rh/candidatures/' . $idCandidature . '/entretien'
                )->with(
                    'succes',
                    'Candidat présélectionné. Vous pouvez programmer un entretien.'
                );
            }

            return redirect()->to(
                '/rh/candidatures/' . $idCandidature
            )->with('succes', 'Candidature refusée.');

        } catch (\Throwable $e) {
            log_message('error', $e->getMessage());

            return redirect()->back()
                ->with('erreur', 'Impossible d’enregistrer la décision.');
        }
    }

    /**
     * Afficher le formulaire de programmation d'entretien.
     */
    public function entretien($idCandidature)
    {
        $candidature = $this->candidatureModel
                            ->find($idCandidature);

        if (!$candidature) {
            return redirect()->back()
                ->with('erreur', 'Candidature introuvable.');
        }

        if ($candidature['statut'] !== 'PRESELECTIONNEE') {
            return redirect()->to(
                '/rh/candidatures/' . $idCandidature
            )->with(
                'erreur',
                'Seuls les candidats présélectionnés peuvent passer à la programmation d’un entretien.'
            );
        }

        return view('rh/candidatures/entretien', [
            'candidature' => $candidature
        ]);
    }
}
