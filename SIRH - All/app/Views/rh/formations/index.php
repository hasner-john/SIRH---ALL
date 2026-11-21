<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3"><?= esc($titre) ?></h1>
<div class="mb-3"><a class="btn btn-outline-primary btn-sm" href="<?= site_url('rh/formations/propositions') ?>">Propositions</a>
  <a class="btn btn-outline-primary btn-sm" href="<?= site_url('rh/formations/assigner') ?>">Assigner</a>
  <a class="btn btn-outline-primary btn-sm" href="<?= site_url('rh/formations/apport') ?>">Apport</a></div>
<form method="post" action="<?= site_url('rh/formations') ?>" class="row g-2 mb-4 bg-white border rounded p-3"><?= csrf_field() ?>
  <div class="col-md-3"><input class="form-control" name="titre" placeholder="Titre" required></div>
  <div class="col-md-2"><input class="form-control" name="prestataire" placeholder="Prestataire"></div>
  <div class="col-md-2"><select class="form-select" name="id_competence"><option value="">Compétence…</option><?php foreach ($competences as $c): ?><option value="<?= (int) $c['id_competence'] ?>"><?= esc($c['code']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><input class="form-control" type="date" name="date_debut" required></div>
  <div class="col-md-2"><input class="form-control" type="date" name="date_fin" required></div>
  <div class="col-md-1"><input class="form-control" type="number" min="0" name="cout" value="0"></div>
  <div class="col-12"><input class="form-control" name="description" placeholder="Description"></div>
  <div class="col-12"><button class="btn btn-primary">Créer la formation</button></div>
</form>
<table class="table table-sm bg-white"><thead><tr><th>Titre</th><th>Compétence</th><th>Dates</th><th>Participants</th><th>Statut</th><th></th></tr></thead><tbody>
<?php foreach ($formations as $f): ?><tr><td><?= esc($f['titre']) ?></td><td><?= esc($f['competence']) ?></td><td><?= esc($f['date_debut'] . ' → ' . $f['date_fin']) ?></td><td><?= (int) $f['nb'] ?></td><td><?= esc($f['statut']) ?></td>
  <td><a href="<?= site_url('rh/formations/' . (int) $f['id_formation']) ?>">Fiche</a></td></tr><?php endforeach; ?></tbody></table>
<?= $this->endSection() ?>
