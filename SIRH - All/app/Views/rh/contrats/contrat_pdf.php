<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8">
<style>
  body{font-family:DejaVu Sans,sans-serif;font-size:11pt;line-height:1.4;margin:30px}
  h1{text-align:center;font-size:16pt;margin-bottom:4px} h2{font-size:12pt;border-bottom:1px solid #999;margin-top:16px}
  .ref{text-align:center;color:#666;font-size:10pt;margin-bottom:16px}
  table{width:100%;border-collapse:collapse;margin-top:6px} td,th{padding:4px 6px;border:1px solid #ccc;vertical-align:top} th{background:#eee;text-align:left;width:30%}
  .sig{margin-top:40px;width:100%} .sig td{border:0;width:50%;height:90px}
</style></head><body>
<h1>CONTRAT DE TRAVAIL</h1>
<div class="ref">Référence : <strong><?= esc($c['reference']) ?></strong> — <?= esc($entreprise->raisonSociale) ?>, <?= esc($entreprise->adresse) ?></div>
<h2>Parties</h2>
<table>
  <tr><th>Employeur</th><td><?= esc($entreprise->raisonSociale) ?> — <?= esc($entreprise->adresse) ?> (STAT <?= esc($entreprise->stat) ?>, CIF <?= esc($entreprise->cif) ?>)</td></tr>
  <tr><th>Salarié</th><td><?= esc(trim(($pers['prenom'] ?? '') . ' ' . ($pers['nom'] ?? ''))) ?><br><?= esc($pers['adresse'] ?? '') ?><br>Tél : <?= esc($pers['telephone'] ?? '') ?> — <?= esc($pers['email'] ?? '') ?></td></tr>
</table>
<h2>Engagement</h2>
<table>
  <tr><th>Poste</th><td><?= esc($poste['libelle'] ?? '') ?> (<?= esc($poste['departement'] ?? '') ?>)</td></tr>
  <tr><th>Type de contrat</th><td><?= esc($c['type_contrat']) ?></td></tr>
  <tr><th>Début</th><td><?= esc($c['date_debut']) ?></td></tr>
  <tr><th>Fin</th><td><?= $c['date_fin'] ? esc($c['date_fin']) : 'Indéterminée' ?></td></tr>
  <tr><th>Rémunération</th><td><?= number_format((float) $c['salaire'], 0, ',', ' ') ?> Ar (<?= esc($c['periodicite_salaire'] ?: 'MENSUEL') ?>)</td></tr>
  <tr><th>Lieu de travail</th><td><?= esc($c['lieu_travail']) ?></td></tr>
  <tr><th>Missions</th><td><?= nl2br(esc((string) $c['missions'])) ?></td></tr>
  <tr><th>Horaires</th><td><?= esc($c['horaires']) ?></td></tr>
  <tr><th>Congés et absences</th><td><?= esc($c['conges_absences']) ?></td></tr>
</table>
<?php if ($conditions): ?><h2>Conditions</h2><table>
  <?php foreach ($conditions as $k): ?><tr><th><?= esc($k['libelle']) ?></th><td><?= esc($k['valeur_finale']) ?></td></tr><?php endforeach; ?></table><?php endif; ?>
<table class="sig"><tr><td>L'employeur<br><small>Signature</small></td><td>Le salarié<br><small>Signature précédée de « Lu et approuvé »</small>
  <?php if ($c['statut'] === 'SIGNE' && $c['date_signature']): ?><br><small>Signé le <?= esc($c['date_signature']) ?></small><?php endif; ?></td></tr></table>
</body></html>
