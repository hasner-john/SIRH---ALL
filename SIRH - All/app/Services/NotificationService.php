<?php

declare(strict_types=1);

namespace App\Services;

use Config\Database;

/** Point d'entrée unique pour toute notification. */
class NotificationService
{
    public static function envoyer(int $idUtilisateur, string $titre, string $message, ?string $lien = null): void
    {
        if ($idUtilisateur <= 0) {
            return;
        }
        Database::connect()->table('notification')->insert([
            'id_utilisateur' => $idUtilisateur,
            'titre' => $titre,
            'message' => $message,
            'lien' => $lien,
        ]);
    }

    /** Notifie le compte d'un employé (employe.id_personne → personne.id_utilisateur). */
    public static function versEmploye(int $idEmploye, string $titre, string $message, ?string $lien = null): void
    {
        $r = Database::connect()->table('employe e')->select('p.id_utilisateur')
            ->join('personne p', 'p.id_personne = e.id_personne')->where('e.id_employe', $idEmploye)->get()->getRowArray();
        if ($r) {
            self::envoyer((int) $r['id_utilisateur'], $titre, $message, $lien);
        }
    }

    /** Notifie tous les comptes RH actifs. */
    public static function versRh(string $titre, string $message, ?string $lien = null): void
    {
        $rows = Database::connect()->table('utilisateur')->select('id_utilisateur')->where('role', 'RH')->where('actif', 1)->get()->getResultArray();
        foreach ($rows as $r) {
            self::envoyer((int) $r['id_utilisateur'], $titre, $message, $lien);
        }
    }
}
