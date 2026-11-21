<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3"><?= esc($titre) ?></h1>
<form method="post" action="<?= site_url('rh/carriere/propositions/generer') ?>" class="mb-3"><?= csrf_field() ?><button class="btn btn-outline-primary btn-sm">Recalculer les propositions</button> <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('rh/parametres') ?>">Seuils (paramètres RH)</a></form>
<?php if (! $rows): ?><div class="alert alert-info">Aucune proposition en attente.</div><?php endif; ?>
<?php foreach ($rows as $r): ?><div class="card mb-3"><div class="card-body">
  <strong><?= esc($r['matricule'] . ' ' . $r['prenom'] . ' ' . $r['nom']) ?></strong> — <span class="badge text-bg-primary"><?= esc($r['type_proposition']) ?></span> <small class="text-muted"><?= esc($r['code_regle']) ?></small>
  <p class="mb-1"><?= esc($r['justification']) ?></p>
  <?php if ($r['salaire_suggere']): ?><p class="mb-1">Salaire suggéré : <?= number_format((float) $r['salaire_suggere'], 0, ',', ' ') ?> Ar</p><?php endif; ?>
  <?php if ($r['poste_suggere'] || $r['fonction_suggeree']): ?><p class="mb-1">Suggestion : <?= esc($r['poste_suggere'] ?: $r['fonction_suggeree']) ?></p><?php endif; ?>
  <div class="d-flex gap-2">
    <form method="post" action="<?= site_url('rh/carriere/propositions/' . (int) $r['id_proposition_carriere'] . '/suivre') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-success">Suivre</button></form>
    <form method="post" action="<?= site_url('rh/carriere/propositions/' . (int) $r['id_proposition_carriere'] . '/refuser') ?>" class="d-flex gap-1"><?= csrf_field() ?><input class="form-control form-control-sm" name="commentaire" placeholder="Commentaire" required><button class="btn btn-sm btn-outline-danger">Refuser</button></form>
  </div></div></div><?php endforeach; ?>
<?= $this->endSection() ?>
