<?php $f = fn ($v) => number_format((float) $v, 2, ',', ' '); ?>
<style>body{font-family:DejaVu Sans,sans-serif;font-size:11px}table{width:100%;border-collapse:collapse}td,th{border:1px solid #999;padding:4px}.r{text-align:right}</style>
<h3><?= esc($entreprise->raisonSociale) ?></h3><div><?= esc($entreprise->adresse) ?> — STAT <?= esc($entreprise->stat) ?> — CIF <?= esc($entreprise->cif) ?></div>
<h2>SOLDE DE TOUT COMPTE</h2>
<p>Salarié : <strong><?= esc($emp['prenom'] . ' ' . $emp['nom']) ?></strong> (<?= esc($emp['matricule']) ?>) — embauché le <?= esc($emp['date_embauche']) ?><br>
Motif de rupture : <?= esc($r['motif']) ?> — notification <?= esc($r['date_notification']) ?> — sortie <?= esc($r['date_sortie']) ?></p>
<table><tr><th>Désignation</th><th>Montant (Ar)</th></tr>
<tr><td>Salaire proratisé (dernier salaire de base : <?= $f($stc['dernier_salaire']) ?>)</td><td class="r"><?= $f($stc['salaire_proratise']) ?></td></tr>
<tr><td>Indemnité de congés (<?= esc($stc['jours_conges_restants']) ?> jour(s))</td><td class="r"><?= $f($stc['indemnite_conges']) ?></td></tr>
<tr><td>Indemnité de rupture</td><td class="r"><?= $f($stc['indemnite_rupture']) ?></td></tr>
<tr><td>− Avances</td><td class="r"><?= $f($stc['avances']) ?></td></tr>
<tr><td>− Retenues</td><td class="r"><?= $f($stc['retenues']) ?></td></tr>
<tr><th>NET À PAYER</th><th class="r"><?= $f($stc['montant_net']) ?></th></tr></table>
<p>Arrêté à la somme de : <strong><?= esc($lettres) ?></strong></p>
<p>Statut : <?= esc($stc['statut']) ?><?= $stc['date_validation'] ? ' — validé le ' . esc($stc['date_validation']) : '' ?></p>
<table style="margin-top:40px"><tr><td style="height:80px">L'employeur</td><td>Le salarié (« Pour solde de tout compte »)</td></tr></table>
