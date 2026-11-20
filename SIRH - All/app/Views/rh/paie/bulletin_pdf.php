<?php $fmt = fn($v) => number_format((float)$v, 2, ',', ' '); ?>
<style>body{font-family:DejaVu Sans,sans-serif;font-size:10px}table{width:100%;border-collapse:collapse}
td,th{border:1px solid #999;padding:3px}.r{text-align:right}h3{margin:8px 0 3px}</style>

<h3><?= esc($entreprise->raisonSociale) ?></h3>
<div><?= esc($entreprise->adresse) ?> — STAT : <?= esc($entreprise->stat) ?> — CIF : <?= esc($entreprise->cif) ?></div>
<h3>BULLETIN DE PAIE — <?= sprintf('%02d/%04d', $b['mois'], $b['annee']) ?></h3>
<table>
<tr><td>Matricule : <?= esc($b['matricule']) ?></td><td>Nom : <?= esc($b['nom'].' '.$b['prenom']) ?></td><td>N° CNaPS : <?= esc($b['numero_cnaps']) ?></td></tr>
<tr><td>Embauche : <?= esc($b['date_embauche']) ?> (<?= $anciennete ?>)</td><td>Catégorie : <?= esc($b['categorie']) ?> — <?= esc($fonction) ?></td><td>Régime : <?= $b['secteur']==='AGRICOLE' ? 'Agricole' : 'Non agricole' ?></td></tr>
<tr><td>Salaire de base : <?= $fmt($b['salaire_base']) ?></td><td>Taux journalier : <?= $fmt($taux_jour) ?></td><td>Taux horaire : <?= $fmt($lignes[0]['taux'] ?? 0) ?></td></tr>
</table>

<h3>Détail</h3>
<table><tr><th>Code</th><th>Désignation</th><th>Base / Nombre</th><th>Taux / Coef</th><th>Gains (Ar)</th><th>Retenues (Ar)</th></tr>
<?php foreach ($lignes as $l): ?><tr>
<td><?= esc($l['code']) ?></td><td><?= esc($l['designation']) ?></td>
<td class="r"><?= $l['base_nombre'] !== null ? $fmt($l['base_nombre']) : '' ?></td>
<td class="r"><?= $l['taux'] !== null ? $fmt($l['taux']) : '' ?></td>
<td class="r"><?= $l['gain'] > 0 ? $fmt($l['gain']) : '' ?></td>
<td class="r"><?= $l['retenue'] > 0 ? $fmt($l['retenue']) : '' ?></td></tr><?php endforeach; ?></table>

<h3>Récapitulatif fiscal et charges patronales</h3>
<table><tr><th>Tranche IRSA</th><th>Taux</th><th>Base</th><th>Impôt</th></tr>
<?php foreach ($tranches as $t): ?><tr><td><?= number_format($t['inf'],0,',',' ') ?> → <?= $t['sup'] === null ? '∞' : number_format((float)$t['sup'],0,',',' ') ?></td>
<td class="r"><?= $t['taux']*100 ?> %</td><td class="r"><?= $fmt($t['base']) ?></td><td class="r"><?= $fmt($t['montant']) ?></td></tr><?php endforeach; ?></table>
<p>Charges patronales : CNaPS <?= $fmt($b['cnaps_patronal']) ?> — OSTIE <?= $fmt($b['ostie_patronal']) ?><br>
Cumuls <?= (int)$b['annee'] ?> : brut <?= $fmt($cumuls['brut']) ?> — IRSA <?= $fmt($cumuls['irsa']) ?><br>
Congés payés : acquis <?= $conges['acquis'] ?> / pris <?= $conges['pris'] ?> / solde <?= $conges['acquis'] + $conges['ajuste'] - $conges['pris'] ?></p>

<h3>NET À PAYER : <?= $fmt($b['net_a_payer']) ?> Ar</h3>
<p><?= esc($lettres) ?></p>
<p>Mode de règlement : <?= esc($b['mode_reglement']) ?> — Date de paiement : <?= esc($b['date_paiement'] ?? '—') ?><br>
<em>Bulletin à conserver sans limitation de durée.</em></p>