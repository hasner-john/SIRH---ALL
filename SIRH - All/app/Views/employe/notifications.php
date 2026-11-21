<?= $this->extend('employe/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3"><?= esc($titre) ?></h1>
<form method="post" action="<?= site_url('employe/notifications/tout-marquer-lu') ?>" class="mb-3"><?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary">Tout marquer lu</button></form>
<?php foreach ($notifications as $n): ?><div class="card mb-2 <?= $n['lue'] ? '' : 'border-success' ?>"><div class="card-body py-2 d-flex justify-content-between">
  <div><strong><?= esc($n['titre']) ?></strong> <small class="text-muted"><?= esc($n['date_creation']) ?></small><br><?= esc($n['message']) ?><?php if ($n['lien']): ?> <a href="<?= site_url(ltrim($n['lien'], '/')) ?>">Ouvrir</a><?php endif; ?></div>
  <?php if (! $n['lue']): ?><form method="post" action="<?= site_url('employe/notifications/' . (int) $n['id_notification'] . '/marquer-lue') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-success">Lue</button></form><?php endif; ?></div></div><?php endforeach; ?>
<?php if (! $notifications): ?><p class="text-muted">Aucune notification.</p><?php endif; ?>
<?= $this->endSection() ?>
