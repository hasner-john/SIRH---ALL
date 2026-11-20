<?php

declare(strict_types=1);

namespace App\Services;

use Config\Database;

class PaieService
{
    private $db;
    private array $cache = [];
    private string $dateRef = '';

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function calculerPeriode(int $periodeId, int $userId = 0, ?string $ip = null): int
    {
        $periode = $this->db->table('periode_paie')->where('id_periode', $periodeId)->get()->getRowArray();
        if (! $periode) {
            throw new \RuntimeException('Période de paie introuvable.');
        }
        if (! in_array($periode['statut'], ['OUVERT', 'CALCUL'], true)) {
            throw new \RuntimeException('Période verrouillée : recalcul interdit (historique immuable).');
        }
        $verrouilles = $this->db->table('bulletin_paie')->where('id_periode', $periodeId)
            ->where('statut !=', 'BROUILLON')->countAllResults();
        if ($verrouilles > 0) {
            throw new \RuntimeException('Des bulletins validés existent : recalcul interdit.');
        }

        $debut = sprintf('%04d-%02d-01', $periode['annee'], $periode['mois']);
        $fin = date('Y-m-t', strtotime($debut));
        $this->dateRef = $fin;
        $this->cache = [];

        $employes = $this->db->table('employe e')
            ->select('e.*, c.salaire')
            ->join('contrat c', 'c.id_contrat = e.id_contrat')
            ->where('e.statut', 'ACTIF')
            ->where('e.date_embauche <=', $fin)
            ->get()->getResultArray();

        $this->db->transBegin();
        try {
            // Seuls des brouillons peuvent être remplacés
            $ids = array_column($this->db->table('bulletin_paie')->select('id_bulletin')
                ->where('id_periode', $periodeId)->get()->getResultArray(), 'id_bulletin');
            if ($ids !== []) {
                $this->db->table('ligne_bulletin')->whereIn('id_bulletin', $ids)->delete();
                $this->db->table('bulletin_paie')->where('id_periode', $periodeId)->delete();
            }
            foreach ($employes as $e) {
                $this->calculerEmploye($e, $periode, $debut, $fin, $userId, $ip);
            }
            $this->db->table('periode_paie')->where('id_periode', $periodeId)->update(['statut' => 'CALCUL']);
            if (! $this->db->transStatus()) {
                throw new \RuntimeException('Erreur base de données pendant le calcul.');
            }
            $this->db->transCommit();
        } catch (\Throwable $t) {
            $this->db->transRollback();
            throw $t;
        }
        return count($employes);
    }

    private function calculerEmploye(array $e, array $periode, string $debut, string $fin, int $userId, ?string $ip): void
    {
        $idE = (int) $e['id_employe'];
        $idP = (int) $periode['id_periode'];
        $sect = $e['secteur'] ?: 'NON_AGRICOLE';

        $base = $this->salaireA($idE, $fin, (float) $e['salaire']);
        $tauxH = $base / $this->p('DIVISEUR_HEURES', $sect);
        $tauxJ = $base / $this->p('DIVISEUR_JOURS', $sect);

        $hAbs = $this->heuresAbsence($idE, $debut, $fin, $sect);
        $retAbs = round($hAbs * $tauxH, 2);
        $baseMois = max(0, round($base - $retAbs, 2));

        $indem = $this->variable($idP, $idE, 'INDEMNITE');
        $rappel = $this->variable($idP, $idE, 'RAPPEL');
        $autres = $this->variable($idP, $idE, 'RETENUE');
        [$majo, $lignesHs] = $this->majorations($idE, $debut, $fin, $tauxH, $sect);

        $brut = round($baseMois + $indem + $rappel + $majo, 2);

        $plafond = $this->p('SME', $sect) * $this->p('MULT_PLAFOND_CNAPS', $sect);
        $assiette = min($brut, $plafond);
        $tcs = $this->p('CNAPS_SALARIAL', $sect);
        $tcp = $this->p('CNAPS_PATRONAL', $sect);
        $tos = $this->p('OSTIE_SALARIAL', $sect);
        $top = $this->p('OSTIE_PATRONAL', $sect);

        $cnapsS = round($assiette * $tcs / 100, 2);
        $cnapsP = round($assiette * $tcp / 100, 2);
        $ostieS = round($brut * $tos / 100, 2);
        $ostieP = round($brut * $top / 100, 2);
        $netImp = max(0, round($brut - $cnapsS - $ostieS, 2));
        $irsa = $this->calculerIrsa($netImp, $fin);
        $totRet = round($cnapsS + $ostieS + $irsa + $autres, 2);
        $net = round($brut - $totRet, 2);

        $this->db->table('bulletin_paie')->insert([
            'id_periode' => $idP, 'id_employe' => $idE,
            'salaire_base' => $base, 'base_mois' => $baseMois, 'retenue_absence' => $retAbs,
            'heures_absence' => $hAbs, 'indemnites' => $indem, 'rappels' => $rappel, 'majorations' => $majo,
            'salaire_brut' => $brut,
            'cnaps_salarial' => $cnapsS, 'cnaps_patronal' => $cnapsP,
            'ostie_salarial' => $ostieS, 'ostie_patronal' => $ostieP,
            'net_imposable' => $netImp, 'irsa' => $irsa,
            'total_retenues' => $totRet, 'net_a_payer' => $net, 'statut' => 'BROUILLON',
        ]);
        $idB = (int) $this->db->insertID();

        $o = 1;
        $l = [
            ['1000', 'Salaire de base du mois', $this->p('DIVISEUR_HEURES', $sect), round($tauxH, 4), $baseMois, 0],
            ['1010', 'Retenue pour absence', $hAbs, round($tauxH, 4), 0, $retAbs],
        ];
        if ($indem > 0)  { $l[] = ['2000', 'Indemnités', null, null, $indem, 0]; }
        if ($rappel > 0) { $l[] = ['2100', 'Rappels / autres', null, null, $rappel, 0]; }
        foreach ($lignesHs as $h) { $l[] = [$h['code'], $h['designation'], $h['nb'], $h['coef'], $h['montant'], 0]; }
        $l[] = ['BRUT', 'TOTAL SALAIRE BRUT', null, null, $brut, 0];
        $l[] = ['4010', 'CNaPS salariale', $assiette, $tcs, 0, $cnapsS];
        $l[] = ['4020', 'OSTIE salariale', $brut, $tos, 0, $ostieS];
        $l[] = ['5000', 'IRSA (par tranches)', $netImp, null, 0, $irsa];
        if ($autres > 0) { $l[] = ['6000', 'Acomptes / avances', null, null, 0, $autres]; }

        $rows = [];
        foreach ($l as [$code, $des, $nb, $taux, $gain, $ret]) {
            $rows[] = ['id_bulletin' => $idB, 'code' => $code, 'designation' => $des, 'base_nombre' => $nb,
                'taux' => $taux, 'gain' => $gain, 'retenue' => $ret, 'ordre_affichage' => $o++];
        }
        $this->db->table('ligne_bulletin')->insertBatch($rows);

        // RG-TRANS-01 : piste d'audit
        $this->db->table('audit_rh')->insert([
            'id_utilisateur' => $userId ?: null, 'action' => 'CREATE', 'objet' => 'bulletin_paie',
            'objet_id' => $idB, 'ancienne_valeur' => null,
            'nouvelle_valeur' => json_encode(['brut' => $brut, 'net' => $net], JSON_UNESCAPED_UNICODE),
            'motif' => sprintf('Calcul paie %02d/%04d (taux jour %.2f)', $periode['mois'], $periode['annee'], $tauxJ),
            'adresse_ip' => $ip,
        ]);
    }

    /** Majorations : HS 30 % (12 premières h/semaine), 50 % (suivantes), plafond 20 h/semaine,
     *  puis cumul multiplicatif dimanche (x2) et nuit (x1,5). */
    private function majorations(int $idE, string $debut, string $fin, float $tauxH, string $sect): array
    {
        $rows = $this->db->table('heure_supplementaire')
            ->where('id_employe', $idE)->where('statut', 'VALIDE')
            ->where('date_travail >=', $debut)->where('date_travail <=', $fin)
            ->orderBy('date_travail')->get()->getResultArray();

        $seuil = $this->p('SEUIL_HS_1', $sect);
        $plaf = $this->p('PLAFOND_HS', $sect);
        $c1 = $this->p('COEF_HS_1', $sect);
        $c2 = $this->p('COEF_HS_2', $sect);
        $cDim = $this->p('COEF_DIMANCHE', $sect);
        $cNuit = $this->p('COEF_NUIT', $sect);

        $cumul = [];
        $agg = [];
        $total = 0.0;
        foreach ($rows as $r) {
            $ts = strtotime($r['date_travail']);
            $sem = date('o-W', $ts);
            $deja = $cumul[$sem] ?? 0.0;
            $h = min((float) $r['heures'], max(0.0, $plaf - $deja));
            if ($h <= 0) {
                continue;
            }
            $h1 = max(0.0, min($h, $seuil - $deja));
            $h2 = $h - $h1;
            $cumul[$sem] = $deja + $h;
            $mult = (date('N', $ts) === '7' ? $cDim : 1) * ($r['nuit'] ? $cNuit : 1);

            foreach ([[$h1, $c1, '3010'], [$h2, $c2, '3020']] as [$hh, $c, $code]) {
                if ($hh <= 0) {
                    continue;
                }
                $coef = round($c * $mult, 4);
                $code = $mult > 1 ? '3030' : $code;
                $k = $code . '|' . $coef;
                $m = round($hh * $tauxH * $coef, 2);
                $agg[$k] ??= ['code' => $code, 'designation' => 'Majoration x' . $coef, 'nb' => 0, 'coef' => $coef, 'montant' => 0];
                $agg[$k]['nb'] += $hh;
                $agg[$k]['montant'] += $m;
                $total += $m;
            }
        }
        return [round($total, 2), array_values($agg)];
    }

    private function heuresAbsence(int $idE, string $debut, string $fin, string $sect): float
    {
        $rows = $this->db->table('absence')->where('id_employe', $idE)->where('justifiee', 0)
            ->where('date_fin >=', $debut)->where('date_debut <=', $fin)->get()->getResultArray();
        $jours = 0;
        foreach ($rows as $r) {
            $d = max($r['date_debut'], $debut);   // on ne compte que les jours du mois
            $f = min($r['date_fin'], $fin);
            $jours += (strtotime($f) - strtotime($d)) / 86400 + 1;
        }
        return (float) ($jours * $this->p('HEURES_JOUR', $sect));
    }

    private function salaireA(int $idE, string $date, float $repli): float
    {
        $r = $this->db->table('historique_salaire')->where('id_employe', $idE)
            ->where('date_effet <=', $date)->orderBy('date_effet', 'DESC')->orderBy('id_historique', 'DESC')
            ->get(1)->getRowArray();
        return $r ? (float) $r['nouveau_salaire'] : $repli;
    }

    private function variable(int $idP, int $idE, string $code): float
    {
        $row = $this->db->table('variable_paie v')->selectSum('v.montant', 'm')
            ->join('rubrique_paie r', 'r.id_rubrique = v.id_rubrique')
            ->where('v.id_periode', $idP)->where('v.id_employe', $idE)->where('r.code', $code)
            ->get()->getRowArray();
        return (float) ($row['m'] ?? 0);
    }

    private function p(string $code, string $secteur): float
    {
        $k = $code . '|' . $secteur;
        if (isset($this->cache[$k])) {
            return $this->cache[$k];
        }
        $ref = $this->dateRef ?: date('Y-m-d');
        $row = $this->db->table('parametre_paie')->where('code', $code)->where('secteur', $secteur)
            ->where('date_effet <=', $ref)
            ->groupStart()->where('date_fin', null)->orWhere('date_fin >=', $ref)->groupEnd()
            ->orderBy('date_effet', 'DESC')->get(1)->getRowArray();
        if (! $row) {
            throw new \RuntimeException("Paramètre de paie manquant : $code ($secteur) au $ref.");
        }
        return $this->cache[$k] = (float) $row['valeur'];
    }

    public function detailIrsa(float $revenu, ?string $date = null): array
    {
        $date ??= date('Y-m-d');
        $tr = $this->db->table('bareme_irsa')->where('date_effet <=', $date)
            ->groupStart()->where('date_fin', null)->orWhere('date_fin >=', $date)->groupEnd()
            ->orderBy('borne_inf')->get()->getResultArray();
        if (! $tr) {
            throw new \RuntimeException('Barème IRSA introuvable.');
        }
        $detail = [];
        foreach ($tr as $t) {
            $inf = (float) $t['borne_inf'];
            $sup = $t['borne_sup'] === null ? INF : (float) $t['borne_sup'];
            if ($revenu <= $inf) {
                break;
            }
            $part = min($revenu, $sup) - $inf;
            $detail[] = ['inf' => $inf, 'sup' => $t['borne_sup'], 'taux' => (float) $t['taux'],
                'base' => $part, 'montant' => round($part * (float) $t['taux'], 2)];
        }
        return $detail;
    }

    public function calculerIrsa(float $revenu, ?string $date = null): float
    {
        return round(array_sum(array_column($this->detailIrsa($revenu, $date), 'montant')), 2);
    }
}