<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3"><?= esc($titre) ?></h1>
<form method="post" action="<?= site_url('rh/parametres') ?>" class="row g-2 mb-3 bg-white border rounded p-2 align-items-center"><?= csrf_field() ?>
  <input type="hidden" name="code" value="SEUIL_ECART_FORMATION"><input type="hidden" name="actif" value="1">
  <div class="col-auto">Seuil d'écart :</div><div class="col-auto"><input class="form-control form-control-sm" style="width:90px" type="number" step="any" name="valeur" value="<?= esc($seuil) ?>"></div>
  <div class="col-auto"><button class="btn btn-sm btn-primary">Enregistrer</button></div>
  <div class="col-auto"><button class="btn btn-sm btn-outline-secondary" formaction="<?= site_url('rh/formations/propositions/recalculer') ?>">Recalculer les propositions</button></div>
</form>
<?php if (! $props): ?><div class="alert alert-info">Aucune proposition en attente.</div><?php endif; ?>
<?php foreach ($props as $p): ?>
<div class="card mb-3"><div class="card-body">
  <strong><?= esc($p['matricule'] . ' ' . $p['prenom'] . ' ' . $p['nom']) ?></strong> — <?= esc($p['code'] . ' ' . $p['competence']) ?> — écart <span class="badge text-bg-danger"><?= (int) $p['ecart'] ?></span> (seuil appliqué <?= esc($p['seuil_applique'] + 0) ?>)
  <form method="post" action="<?= site_url('rh/formations/propositions/' . (int) $p['id_proposition_formation'] . '/assigner') ?>" class="row g-2 mt-2"><?= csrf_field() ?>
    <div class="col-md-3"><select class="form-select form-select-sm" name="id_formation"><option value="">Nouvelle formation…</option><?php foreach ($formations as $f): ?><option value="<?= (int) $f['id_formation'] ?>" <?= (int) $f['id_competence'] === (int) $p['id_competence'] ? 'style="font-weight:bold"' : '' ?>><?= esc($f['titre']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-3"><input class="form-control form-control-sm" name="titre" placeholder="Titre (si nouvelle)"></div>
    <div class="col-md-2"><input class="form-control form-control-sm" type="date" name="date_debut"></div>
    <div class="col-md-2"><input class="form-control form-control-sm" type="date" name="date_fin"></div>
    <div class="col-md-2"><button class="btn btn-sm btn-success">Assigner</button></div>
  </form>
  <form method="post" action="<?= site_url('rh/formations/propositions/' . (int) $p['id_proposition_formation'] . '/refuser') ?>" class="d-flex gap-2 mt-2"><?= csrf_field() ?>
    <input class="form-control form-control-sm" name="commentaire" placeholder="Commentaire (obligatoire pour refuser)" required><button class="btn btn-sm btn-outline-danger">Refuser</button></form>
</div></div>
<?php endforeach; ?>
<?= $this->endSection() ?>
