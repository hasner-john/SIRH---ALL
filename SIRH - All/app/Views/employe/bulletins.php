<?= $this->extend('employe/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3"><?= esc($titre) ?></h1>
<table class="table bg-white"><thead><tr><th>Période</th><th>Net à payer</th><th>Statut</th><th></th></tr></thead><tbody>
<?php foreach ($bulletins as $b): ?><tr><td><?= sprintf('%02d/%04d', $b['mois'], $b['annee']) ?></td><td><?= number_format((float) $b['net_a_payer'], 0, ',', ' ') ?> Ar</td><td><?= esc($b['statut']) ?></td><td><a target="_blank" href="<?= site_url('employe/bulletins/' . (int) $b['id_bulletin'] . '/pdf') ?>">PDF</a></td></tr><?php endforeach; ?></tbody></table>
<?= $this->endSection() ?>
