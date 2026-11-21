<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3"><?= esc($titre) ?></h1>
<div class="mb-3"><a class="btn btn-outline-primary btn-sm" href="<?= site_url('rh/discipline/sanctions') ?>">Sanctions</a> <a class="btn btn-outline-primary btn-sm" href="<?= site_url('rh/discipline/reponses') ?>">Réponses des employés</a></div>
<form method="post" action="<?= site_url('rh/discipline/incidents') ?>" class="row g-2 mb-4 bg-white border rounded p-3"><?= csrf_field() ?>
  <div class="col-md-3"><select class="form-select" name="id_employe" required><option value="">Employé…</option><?php foreach ($employes as $e): ?><option value="<?= (int) $e['id_employe'] ?>"><?= esc($e['matricule'] . ' — ' . $e['prenom'] . ' ' . $e['nom']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><input class="form-control" type="date" name="date_incident" value="<?= date('Y-m-d') ?>" required></div>
  <div class="col-md-2"><select class="form-select" name="categorie"><?php foreach (['RETARD', 'ABSENCE', 'FAUTE_PROFESSIONNELLE', 'COMPORTEMENT', 'SECURITE', 'AUTRE'] as $c): ?><option><?= $c ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><select class="form-select" name="gravite"><?php foreach (['MINEURE', 'MODEREE', 'GRAVE', 'TRES_GRAVE'] as $c): ?><option <?= $c === 'MODEREE' ? 'selected' : '' ?>><?= $c ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><input class="form-control" name="objet" placeholder="Objet" required></div>
  <div class="col-md-9"><input class="form-control" name="description" placeholder="Description" required></div>
  <div class="col-md-3"><button class="btn btn-danger">Signaler l'incident</button></div>
</form>
<form method="get" class="row g-2 mb-3">
  <div class="col-md-2"><select class="form-select" name="statut"><option value="">Statut</option><?php foreach (['SIGNALE', 'EXPLICATIONS', 'REPONSE', 'COMMISSION', 'DECIDE', 'SANCTION', 'CLOTURE'] as $s): ?><option <?= ($filtre['statut'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><select class="form-select" name="gravite"><option value="">Gravité</option><?php foreach (['MINEURE', 'MODEREE', 'GRAVE', 'TRES_GRAVE'] as $s): ?><option <?= ($filtre['gravite'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><select class="form-select" name="categorie"><option value="">Catégorie</option><?php foreach (['RETARD', 'ABSENCE', 'FAUTE_PROFESSIONNELLE', 'COMPORTEMENT', 'SECURITE', 'AUTRE'] as $s): ?><option <?= ($filtre['categorie'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><select class="form-select" name="id_employe"><option value="">Employé</option><?php foreach ($employes as $e): ?><option value="<?= (int) $e['id_employe'] ?>" <?= ((int) ($filtre['id_employe'] ?? 0)) === (int) $e['id_employe'] ? 'selected' : '' ?>><?= esc($e['matricule']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><input class="form-control" type="date" name="du" value="<?= esc($filtre['du'] ?? '') ?>"></div><div class="col-md-2"><input class="form-control" type="date" name="au" value="<?= esc($filtre['au'] ?? '') ?>"></div>
  <div class="col-12"><button class="btn btn-outline-primary btn-sm">Filtrer</button></div>
</form>
<table class="table table-sm bg-white"><thead><tr><th>#</th><th>Date</th><th>Employé</th><th>Objet</th><th>Catégorie</th><th>Gravité</th><th>Statut</th><th></th></tr></thead><tbody>
<?php foreach ($incidents as $i): ?><tr><td><?= (int) $i['id_incident'] ?></td><td><?= esc($i['date_incident']) ?></td><td><?= esc($i['matricule'] . ' ' . $i['nom']) ?></td><td><?= esc($i['objet']) ?></td><td><?= esc($i['categorie']) ?></td><td><?= esc($i['gravite']) ?></td><td><?= esc($i['statut']) ?></td><td><a href="<?= site_url('rh/discipline/incidents/' . (int) $i['id_incident']) ?>">Dossier</a></td></tr><?php endforeach; ?></tbody></table>
<?= $this->endSection() ?>
