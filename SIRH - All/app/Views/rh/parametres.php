<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3"><?= esc($titre) ?></h1>
<?php $noms = array_keys($groupes); ?>
<ul class="nav nav-tabs">
  <?php foreach ($noms as $i => $g): ?><li class="nav-item"><button class="nav-link <?= $i === 0 ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#g-<?= esc($g) ?>" type="button"><?= esc($g) ?></button></li><?php endforeach; ?>
</ul>
<div class="tab-content border border-top-0 bg-white p-3">
  <?php foreach ($groupes as $g => $rows): ?>
  <div class="tab-pane fade <?= $g === $noms[0] ? 'show active' : '' ?>" id="g-<?= esc($g) ?>">
    <?php foreach ($rows as $r): ?>
    <form method="post" action="<?= site_url('rh/parametres') ?>" class="row g-2 align-items-center mb-2">
      <?= csrf_field() ?><input type="hidden" name="code" value="<?= esc($r['code']) ?>">
      <div class="col-md-6"><?= esc($r['libelle']) ?> <small class="text-muted">(<?= esc($r['code']) ?>)</small></div>
      <div class="col-md-2"><input class="form-control form-control-sm" type="number" step="any" name="valeur" value="<?= esc($r['valeur'] + 0) ?>"></div>
      <div class="col-md-1 small"><?= esc($r['unite']) ?></div>
      <div class="col-md-1"><input class="form-check-input" type="checkbox" name="actif" value="1" <?= $r['actif'] ? 'checked' : '' ?>> actif</div>
      <div class="col-md-2"><button class="btn btn-sm btn-primary">Enregistrer</button></div>
    </form>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>
</div>
<?= $this->endSection() ?>
