<?php

declare(strict_types=1);

namespace App\Libraries;

use Config\Database;

/**
 * Règles de changement de statut (un seul endroit). Tout bouton « changer d'état »
 * passe par appliquer() : la transition est refusée avec un message si elle n'existe pas.
 */
class Transitions
{
    /** objet => [statut courant => [statuts autorisés]] */
    public const REGLES = [
        'periode_paie' => ['OUVERT' => ['CALCUL', 'ANNULE'], 'CALCUL' => ['VALIDE', 'ANNULE'], 'VALIDE' => ['PAIE'], 'PAIE' => ['CLOTURE']],
        'bulletin_paie' => ['BROUILLON' => ['VALIDE', 'ANNULE'], 'VALIDE' => ['PAIE']],
        'heure_supplementaire' => ['A_VALIDER' => ['VALIDE', 'REFUSEE']],
        'retard' => ['A_TRAITER' => ['JUSTIFIE', 'VALIDE'], 'JUSTIFIE' => ['VALIDE']],
        'employe' => ['ACTIF' => ['SUSPENDU', 'PARTI'], 'SUSPENDU' => ['ACTIF', 'PARTI']],
        'contrat' => ['BROUILLON' => ['A_SIGNER', 'ANNULE'], 'A_SIGNER' => ['SIGNE', 'ANNULE'], 'SIGNE' => ['REMPLACE', 'TERMINE']],
        'formation' => ['PLANIFIEE' => ['EN_COURS', 'ANNULEE'], 'EN_COURS' => ['TERMINEE', 'ANNULEE']],
        'formation_participant' => ['INSCRIT' => ['PRESENT', 'ABANDON'], 'PRESENT' => ['TERMINE', 'ABANDON']],
        'evaluation_performance' => ['BROUILLON' => ['VALIDE'], 'VALIDE' => ['ARCHIVE']],
        'objectif' => ['OUVERT' => ['EN_COURS', 'ATTEINT', 'NON_ATTEINT', 'ANNULE'], 'EN_COURS' => ['ATTEINT', 'NON_ATTEINT', 'ANNULE']],
        'proposition_formation' => ['EN_ATTENTE' => ['ASSIGNEE', 'REFUSEE', 'SANS_OBJET']],
        'mouvement_carriere' => ['PLANIFIE' => ['APPLIQUE', 'ANNULE']],
        'proposition_carriere' => ['EN_ATTENTE' => ['ACCEPTEE', 'REFUSEE', 'EXPIREE']],
        'incident' => ['SIGNALE' => ['EXPLICATIONS', 'CLOTURE'], 'EXPLICATIONS' => ['REPONSE', 'COMMISSION', 'DECIDE', 'SANCTION', 'CLOTURE'],
            'REPONSE' => ['COMMISSION', 'DECIDE', 'SANCTION', 'CLOTURE'], 'COMMISSION' => ['DECIDE', 'SANCTION', 'CLOTURE'],
            'DECIDE' => ['SANCTION', 'CLOTURE'], 'SANCTION' => ['CLOTURE']],
        'sanction' => ['NOTIFIEE' => ['CONTESTEE', 'EXECUTOIRE', 'ANNULEE'], 'CONTESTEE' => ['EXECUTOIRE', 'ANNULEE']],
        'rupture_contrat' => ['OUVERT' => ['EN_COURS', 'VALIDEE', 'ANNULEE'], 'EN_COURS' => ['VALIDEE', 'ANNULEE'], 'VALIDEE' => ['CLOTURE']],
        'solde_tout_compte' => ['BROUILLON' => ['VALIDE', 'ANNULE'], 'VALIDE' => ['PAIE']],
        'checklist_offboarding' => ['A_FAIRE' => ['EN_COURS', 'TERMINE', 'NON_APPLICABLE'], 'EN_COURS' => ['TERMINE', 'NON_APPLICABLE'],
            'TERMINE' => ['EN_COURS'], 'NON_APPLICABLE' => ['A_FAIRE']],
    ];

    /** Colonne clé et colonne statut par objet (par défaut : id_<objet>, statut). */
    private const COLONNES = [
        'periode_paie' => ['id_periode', 'statut'],
        'bulletin_paie' => ['id_bulletin', 'statut'],
        'heure_supplementaire' => ['id_heure_supplementaire', 'statut'],
        'retard' => ['id_retard', 'statut'],
        'employe' => ['id_employe', 'statut'],
        'contrat' => ['id_contrat', 'statut'],
        'formation' => ['id_formation', 'statut'],
        'formation_participant' => ['id_participant', 'statut'],
        'evaluation_performance' => ['id_evaluation_performance', 'statut'],
        'objectif' => ['id_objectif', 'statut'],
        'proposition_formation' => ['id_proposition_formation', 'statut'],
        'mouvement_carriere' => ['id_mouvement', 'statut'],
        'proposition_carriere' => ['id_proposition_carriere', 'statut'],
        'incident' => ['id_incident', 'statut'],
        'sanction' => ['id_sanction', 'statut'],
        'rupture_contrat' => ['id_rupture', 'statut'],
        'solde_tout_compte' => ['id_stc', 'statut'],
        'checklist_offboarding' => ['id_checklist', 'valeur_statut'],
    ];

    public static function peut(string $objet, string $de, string $vers): bool
    {
        return in_array($vers, self::REGLES[$objet][$de] ?? [], true);
    }

    /**
     * Applique la transition + audit. Retourne [true, ''] ou [false, 'message'].
     * $extra : colonnes supplémentaires à mettre à jour en même temps.
     */
    public static function appliquer(string $objet, int $id, string $vers, ?string $motif = null, array $extra = []): array
    {
        [$pk, $col] = self::COLONNES[$objet] ?? [null, null];
        if ($pk === null) {
            return [false, "Objet inconnu : $objet."];
        }
        $db = Database::connect();
        $row = $db->table($objet)->where($pk, $id)->get()->getRowArray();
        if (! $row) {
            return [false, 'Enregistrement introuvable.'];
        }
        $de = (string) $row[$col];
        if (! self::peut($objet, $de, $vers)) {
            return [false, "Transition refusée : $objet $de → $vers non autorisée."];
        }
        $db->table($objet)->where($pk, $id)->update([$col => $vers] + $extra);
        Audit::log('STATUT', $objet, $id, $de, $vers, $motif);
        return [true, ''];
    }
}
