<?= $this->extend('employe/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3"><?= esc($titre) ?></h1>
<table class="table bg-white"><thead><tr><th>Formation</th><th>Dates</th><th>Prestataire</th><th>Statut</th><th>Niveau</th></tr></thead><tbody>
<?php foreach ($formations as $f): ?><tr><td><?= esc($f['titre']) ?></td><td><?= esc($f['date_debut'] . ' → ' . $f['date_fin']) ?></td><td><?= esc($f['prestataire']) ?></td><td><?= esc($f['statut']) ?></td><td><?= esc(($f['niveau_avant'] ?? '—') . ' → ' . ($f['niveau_apres'] ?? '—')) ?></td></tr><?php endforeach; ?></tbody></table>
<?= $this->endSection() ?>
