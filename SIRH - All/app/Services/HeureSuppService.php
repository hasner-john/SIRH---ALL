<?php

declare(strict_types=1);

namespace App\Services;

use App\Libraries\Audit;
use App\Libraries\ParametrePaie;
use App\Libraries\ParametreRh;
use Config\Database;

class HeureSuppService
{
    /**
     * Découpe [date heureDebut → heureFin] par jour civil puis par plage jour/nuit.
     * Retourne une liste de morceaux : ['date' => Y-m-d, 'nuit' => 0|1, 'heures' => float].
     * Si heureFin <= heureDebut, la fin est le lendemain.
     */
    public function decouper(string $date, string $debut, string $fin): array
    {
        $a = strtotime("$date $debut");
        $b = strtotime("$date $fin");
        if ($a === false || $b === false) {
            throw new \InvalidArgumentException('Date ou heure invalide.');
        }
        if ($b <= $a) {
            $b = strtotime('+1 day', $b);
        }
        $nuitDeb = (int) ParametreRh::get('NUIT_DEBUT_HEURE', 22) * 60;
        $nuitFin = (int) ParametreRh::get('NUIT_FIN_HEURE', 4) * 60;

        $morceaux = [];
        $cur = $a;
        while ($cur < $b) {
            $jour = date('Y-m-d', $cur);
            $minuit = strtotime($jour . ' 00:00:00');
            $finJour = min($b, strtotime('+1 day', $minuit));
            $m1 = (int) (($cur - $minuit) / 60);
            $m2 = (int) (($finJour - $minuit) / 60);
            // plages du jour : nuit [0, nuitFin), jour [nuitFin, nuitDeb), nuit [nuitDeb, 1440)
            foreach ([[0, $nuitFin, 1], [$nuitFin, $nuitDeb, 0], [$nuitDeb, 1440, 1]] as [$x, $y, $nuit]) {
                $o = min($m2, $y) - max($m1, $x);
                if ($o > 0) {
                    $k = $jour . '|' . $nuit;
                    $morceaux[$k] = ['date' => $jour, 'nuit' => $nuit, 'heures' => ($morceaux[$k]['heures'] ?? 0) + $o / 60];
                }
            }
            $cur = $finJour;
        }
        return array_values(array_map(static fn ($m) => $m + ['heures' => round($m['heures'], 2)], $morceaux));
    }

    /** Aperçu : dimanche oui/non, heures de nuit, coefficient estimé. */
    public function apercu(string $date, string $debut, string $fin, string $secteur = 'NON_AGRICOLE'): array
    {
        $m = $this->decouper($date, $debut, $fin);
        $nuit = 0.0;
        $total = 0.0;
        $dimanche = false;
        foreach ($m as $x) {
            $total += $x['heures'];
            $nuit += $x['nuit'] ? $x['heures'] : 0;
            $dimanche = $dimanche || date('N', strtotime($x['date'])) === '7';
        }
        $coef = ParametrePaie::get('COEF_HS_1', $secteur, $date, 1.3);
        if ($dimanche) {
            $coef *= ParametrePaie::get('COEF_DIMANCHE', $secteur, $date, 2);
        }
        if ($nuit > 0) {
            $coef *= ParametrePaie::get('COEF_NUIT', $secteur, $date, 1.5);
        }
        return ['dimanche' => $dimanche, 'nuit' => $nuit, 'total' => $total, 'coefficient' => round($coef, 4)];
    }

    /** Enregistre les HS (RH : directement VALIDE). Lève RuntimeException si plafond hebdomadaire dépassé. */
    public function enregistrer(int $idEmploye, string $date, string $debut, string $fin, ?string $motif, int $userId, bool $valide = true): int
    {
        $db = Database::connect();
        $emp = $db->table('employe')->where('id_employe', $idEmploye)->get()->getRowArray();
        if (! $emp) {
            throw new \RuntimeException('Employé introuvable.');
        }
        $morceaux = $this->decouper($date, $debut, $fin);
        if ($morceaux === []) {
            throw new \RuntimeException('Intervalle vide.');
        }
        $plafond = ParametrePaie::get('PLAFOND_HS', $emp['secteur'] ?: 'NON_AGRICOLE', $date);

        // contrôle du plafond par semaine ISO (HS non refusées déjà enregistrées + nouvelles)
        $parSemaine = [];
        foreach ($morceaux as $m) {
            $sem = date('o-W', strtotime($m['date']));
            $parSemaine[$sem] = ($parSemaine[$sem] ?? 0) + $m['heures'];
        }
        foreach ($parSemaine as $sem => $nouvelles) {
            [$an, $w] = explode('-', $sem);
            $lundi = (new \DateTimeImmutable())->setISODate((int) $an, (int) $w)->format('Y-m-d');
            $dimanche = date('Y-m-d', strtotime($lundi . ' +6 days'));
            $deja = (float) ($db->table('heure_supplementaire')->selectSum('heures', 'h')->where('id_employe', $idEmploye)
                ->where('statut !=', 'REFUSEE')->where('date_travail >=', $lundi)->where('date_travail <=', $dimanche)
                ->get()->getRow('h') ?? 0);
            if ($deja + $nouvelles > $plafond) {
                throw new \RuntimeException(sprintf('Plafond hebdomadaire dépassé (semaine %s) : %.2f h déjà saisies + %.2f h > %.2f h.', $sem, $deja, $nouvelles, $plafond));
            }
        }

        $db->transBegin();
        try {
            foreach ($morceaux as $m) {
                $existe = $db->table('heure_supplementaire')->where(['id_employe' => $idEmploye, 'date_travail' => $m['date'], 'nuit' => $m['nuit']])->get()->getRowArray();
                if ($existe) {
                    $db->table('heure_supplementaire')->where('id_heure_supplementaire', $existe['id_heure_supplementaire'])
                        ->update(['heures' => (float) $existe['heures'] + $m['heures']]);
                    $id = (int) $existe['id_heure_supplementaire'];
                } else {
                    $db->table('heure_supplementaire')->insert([
                        'id_employe' => $idEmploye, 'date_travail' => $m['date'], 'heure_debut' => $debut, 'heure_fin' => $fin,
                        'heures' => $m['heures'], 'nuit' => $m['nuit'], 'motif' => $motif,
                        'statut' => $valide ? 'VALIDE' : 'A_VALIDER', 'id_validateur' => $valide ? $userId : null, 'cree_par' => $userId,
                    ]);
                    $id = (int) $db->insertID();
                }
                Audit::log('CREATE', 'heure_supplementaire', $id, null, $m, $motif);
            }
            if (! $db->transStatus()) {
                throw new \RuntimeException('Erreur base de données.');
            }
            $db->transCommit();
        } catch (\Throwable $t) {
            $db->transRollback();
            throw $t;
        }
        return count($morceaux);
    }
}
