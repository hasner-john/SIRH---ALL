<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3"><?= esc($titre) ?></h1>
<table class="table table-sm bg-white"><thead><tr><th>Employé</th><th>Formation</th><th>Compétence</th><th>Écart avant</th><th>Écart après</th><th>Apport</th><th>Verdict</th></tr></thead><tbody>
<?php foreach ($rows as $r): $a = (int) $r['apport']; ?><tr><td><?= esc($r['matricule'] . ' ' . $r['nom']) ?></td><td><?= esc($r['titre']) ?></td><td><?= esc($r['code']) ?></td>
  <td><?= (int) $r['ecart_avant'] ?></td><td><?= (int) $r['ecart_apres'] ?></td><td><?= $a ?></td>
  <td><?= $a > 0 ? '<span class="badge text-bg-success">Amélioration</span>' : ($a === 0 ? '<span class="badge text-bg-secondary">Stable</span>' : '<span class="badge text-bg-danger">Dégradation</span>') ?></td></tr><?php endforeach; ?></tbody></table>
<?php if (! $rows): ?><p class="text-muted">Aucun apport mesuré pour l'instant.</p><?php endif; ?>
<?= $this->endSection() ?>
