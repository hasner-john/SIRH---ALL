<?php

declare(strict_types=1);

namespace App\Services;

use Config\Database;

class PeriodePaieService
{
    /**
     * Ouvre la période (mois, année) si elle n'existe pas (INSERT OR IGNORE, statut OUVERT).
     * Retourne ['id_periode', 'statut', 'verrouillee'] : verrouillée = VALIDE, PAIE ou CLOTURE.
     */
    public static function assurer(int $mois, int $annee): array
    {
        $db = Database::connect();
        $paiement = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $annee, $mois)));
        $db->query('INSERT OR IGNORE INTO periode_paie (mois, annee, date_paiement, statut) VALUES (?, ?, ?, ?)', [$mois, $annee, $paiement, 'OUVERT']);
        $p = $db->table('periode_paie')->where(['mois' => $mois, 'annee' => $annee])->get()->getRowArray();
        return [
            'id_periode' => (int) $p['id_periode'],
            'statut' => $p['statut'],
            'verrouillee' => in_array($p['statut'], ['VALIDE', 'PAIE', 'CLOTURE', 'ANNULE'], true),
        ];
    }
}
