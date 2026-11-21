<?php

declare(strict_types=1);

namespace App\Libraries;

use Config\Database;

/** Lecture des paramètres de paie datés (table parametre_paie), par secteur et date d'effet. */
class ParametrePaie
{
    private static array $cache = [];

    public static function get(string $code, string $secteur = 'NON_AGRICOLE', ?string $date = null, ?float $defaut = null): float
    {
        $ref = $date ?: date('Y-m-d');
        $k = "$code|$secteur|$ref";
        if (isset(self::$cache[$k])) {
            return self::$cache[$k];
        }
        $row = Database::connect()->table('parametre_paie')->where('code', $code)->where('secteur', $secteur)
            ->where('date_effet <=', $ref)
            ->groupStart()->where('date_fin', null)->orWhere('date_fin >=', $ref)->groupEnd()
            ->orderBy('date_effet', 'DESC')->get(1)->getRowArray();
        if (! $row) {
            if ($defaut !== null) {
                return $defaut;
            }
            throw new \RuntimeException("Paramètre de paie manquant : $code ($secteur) au $ref.");
        }
        return self::$cache[$k] = (float) $row['valeur'];
    }
}
