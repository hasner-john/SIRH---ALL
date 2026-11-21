<?php

declare(strict_types=1);

namespace App\Libraries;

use Config\Database;

/** RG-TRANS-01 : une ligne audit_rh par écriture sensible (date UTC par défaut en base). */
class Audit
{
    public static function log(string $action, string $objet, ?int $objetId, mixed $ancienne = null, mixed $nouvelle = null, ?string $motif = null): void
    {
        $enc = static fn (mixed $v): ?string => $v === null ? null : (is_string($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE));
        $ip = null;
        try {
            $ip = service('request')->getIPAddress();
        } catch (\Throwable) {
        }
        Database::connect()->table('audit_rh')->insert([
            'id_utilisateur' => (int) (session()->get('id_utilisateur') ?? 0) ?: null,
            'action' => $action,
            'objet' => $objet,
            'objet_id' => $objetId,
            'ancienne_valeur' => $enc($ancienne),
            'nouvelle_valeur' => $enc($nouvelle),
            'motif' => $motif,
            'adresse_ip' => $ip,
        ]);
    }

    /** Journal d'accès (RG-TRANS-04). $action : CONSULTATION|CREATION|MODIFICATION|TELECHARGEMENT */
    public static function acces(string $action, string $ressource, ?int $idEmploye = null, ?string $details = null): void
    {
        $ip = null;
        try {
            $ip = service('request')->getIPAddress();
        } catch (\Throwable) {
        }
        Database::connect()->table('journal_acces')->insert([
            'id_utilisateur' => (int) (session()->get('id_utilisateur') ?? 0) ?: null,
            'id_employe' => $idEmploye,
            'action' => $action,
            'ressource' => $ressource,
            'adresse_ip' => $ip,
            'details' => $details,
        ]);
    }
}
