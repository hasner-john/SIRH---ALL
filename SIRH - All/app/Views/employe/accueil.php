<?= $this->extend('employe/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3"><?= esc($titre) ?><?= $emp ? ' — ' . esc($emp['prenom'] . ' ' . $emp['nom']) : '' ?></h1>
<?php if (! $emp): ?><div class="alert alert-warning">Aucune fiche employé n'est liée à ce compte.</div><?php endif; ?>
<div class="row g-3">
  <div class="col-md-4"><div class="card"><div class="card-body"><h2 class="h6">Notifications non lues</h2><p class="display-6"><?= (int) $nonLues ?></p><a href="<?= site_url('employe/notifications') ?>">Voir</a></div></div></div>
  <div class="col-md-4"><div class="card"><div class="card-body"><h2 class="h6">Formations à venir</h2>
    <?php foreach ($formations as $f): ?><div class="small"><?= esc($f['titre'] . ' (' . $f['date_debut'] . ')') ?></div><?php endforeach; ?><?php if (! $formations): ?><span class="text-muted small">Aucune</span><?php endif; ?></div></div></div>
  <div class="col-md-4"><div class="card"><div class="card-body"><h2 class="h6">Explications à fournir</h2>
    <?php foreach ($explications as $x): ?><div class="small"><a href="<?= site_url('employe/discipline') ?>"><?= esc($x['objet']) ?></a> — avant le <?= esc($x['date_limite']) ?></div><?php endforeach; ?><?php if (! $explications): ?><span class="text-muted small">Aucune</span><?php endif; ?></div></div></div>
</div>
<?= $this->endSection() ?>
