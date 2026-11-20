<?php

namespace App\Services;

use App\Models\CandidatureModel;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;

class CandidatureService
{
    protected CandidatureModel $candidatureModel;
    protected BaseConnection $db;

    public function __construct()
    {
        $this->candidatureModel = new CandidatureModel();
        $this->db = \Config\Database::connect();
    }

    /**
     * Vérifier la présence, le type et la taille d'un document.
     *
     * Le chemin enregistré en base est relatif à writable/.
     */
    public function verifierDocument($chemin, $typeDocument)
    {
        $resultat = [
            'present' => false,
            'valide' => false,
            'message' => ''
        ];

        if (empty($chemin)) {
            $resultat['message'] = 'Document absent.';
            return $resultat;
        }

        $cheminComplet = WRITEPATH . $chemin;

        if (!is_file($cheminComplet) || !is_readable($cheminComplet)) {
            $resultat['message'] = 'Fichier introuvable ou inaccessible.';
            return $resultat;
        }

        $resultat['present'] = true;

        // Taille maximale : 5 Mo
        $tailleMax = 5 * 1024 * 1024;

        if (filesize($cheminComplet) > $tailleMax) {
            $resultat['message'] = 'Le fichier dépasse la taille maximale de 5 Mo.';
            return $resultat;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($cheminComplet);

        $typesAutorises = [
            'cv' => ['application/pdf'],
            'lettre_motivation' => ['application/pdf'],
            'cin' => [
                'application/pdf',
                'image/jpeg',
                'image/png'
            ]
        ];

        if (!isset($typesAutorises[$typeDocument])) {
            $resultat['message'] = 'Type de document inconnu.';
            return $resultat;
        }

        if (!in_array($mime, $typesAutorises[$typeDocument], true)) {
            $resultat['message'] = 'Type de fichier non autorisé.';
            return $resultat;
        }

        $resultat['valide'] = true;
        $resultat['message'] = 'Document valide.';

        return $resultat;
    }

    /**
     * Enregistrer une décision RH et son historique.
     */
    public function deciderCandidature(
        $idCandidature,
        $nouveauStatut,
        $idUtilisateur,
        $commentaire = null
    ) {
        $statutsAutorises = ['PRESELECTIONNEE', 'REFUSEE'];

        if (!in_array($nouveauStatut, $statutsAutorises, true)) {
            throw new RuntimeException('Décision inconnue.');
        }

        $candidature = $this->candidatureModel
                            ->find($idCandidature);

        if (!$candidature) {
            throw new RuntimeException('Candidature introuvable.');
        }

        $ancienStatut = $candidature['statut'];

        if ($ancienStatut === $nouveauStatut) {
            throw new RuntimeException(
                'Cette candidature possède déjà ce statut.'
            );
        }

        $this->db->transBegin();

        try {
            $modification = $this->candidatureModel
                                 ->changerStatut(
                                     $idCandidature,
                                     $nouveauStatut
                                 );

            if (!$modification) {
                throw new RuntimeException(
                    'Impossible de modifier le statut.'
                );
            }

            $historique = $this->db
                               ->table('historique_candidature')
                               ->insert([
                                   'id_candidature' => $idCandidature,
                                   'ancien_statut' => $ancienStatut,
                                   'nouveau_statut' => $nouveauStatut,
                                   'id_utilisateur' => $idUtilisateur,
                                   'commentaire' => $commentaire,
                                   'date_action' => date('Y-m-d H:i:s')
                               ]);

            if (!$historique) {
                throw new RuntimeException(
                    'Impossible d’enregistrer l’historique.'
                );
            }

            if ($this->db->transStatus() === false) {
                throw new RuntimeException(
                    'Une erreur est survenue pendant la transaction.'
                );
            }

            $this->db->transCommit();

            return true;

        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}
