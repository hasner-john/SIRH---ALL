<?php

declare(strict_types=1);

namespace App\Libraries;

use Config\Database;

/**
 * Lecture des paramètres RH (table parametre_rh). Un cache par requête.
 * RG-TRANS-05 : aucun seuil, taux ou durée n'est écrit en dur dans le code.
 */
class ParametreRh
{
    private static ?array $cache = null;

    public static function get(string $code, float|int|null $defaut = null): float|int|null
    {
        $row = self::charger()[$code] ?? null;
        if ($row === null || ! (int) $row['actif']) {
            return $defaut;
        }
        return $row['valeur'] + 0;
    }

    /** Une règle (ex. R1_ANCIENNETE) est active si tous ses paramètres sont actifs. */
    public static function actif(string $code): bool
    {
        $row = self::charger()[$code] ?? null;
        return $row !== null && (int) $row['actif'] === 1;
    }

    public static function vider(): void
    {
        self::$cache = null;
    }

    private static function charger(): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            foreach (Database::connect()->table('parametre_rh')->get()->getResultArray() as $r) {
                self::$cache[$r['code']] = $r;
            }
        }
        return self::$cache;
    }
}
