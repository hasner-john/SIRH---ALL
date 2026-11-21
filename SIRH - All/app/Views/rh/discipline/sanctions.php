<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3"><?= esc($titre) ?></h1>
<form method="get" class="row g-2 mb-3">
  <div class="col-md-2"><select class="form-select" name="type_sanction"><option value="">Type</option><?php foreach (['AVERTISSEMENT', 'BLAME', 'MISE_A_PIED', 'LICENCIEMENT', 'AUTRE'] as $s): ?><option <?= ($filtre['type_sanction'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><select class="form-select" name="statut"><option value="">Statut</option><?php foreach (['NOTIFIEE', 'CONTESTEE', 'EXECUTOIRE', 'ANNULEE'] as $s): ?><option <?= ($filtre['statut'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><select class="form-select" name="id_employe"><option value="">Employé</option><?php foreach ($employes as $e): ?><option value="<?= (int) $e['id_employe'] ?>" <?= ((int) ($filtre['id_employe'] ?? 0)) === (int) $e['id_employe'] ? 'selected' : '' ?>><?= esc($e['matricule']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><select class="form-select" name="id_departement"><option value="">Département</option><?php foreach ($departements as $d): ?><option value="<?= (int) $d['id_departement'] ?>" <?= ((int) ($filtre['id_departement'] ?? 0)) === (int) $d['id_departement'] ? 'selected' : '' ?>><?= esc($d['nom']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><select class="form-select" name="gravite"><option value="">Gravité</option><?php foreach (['MINEURE', 'MODEREE', 'GRAVE', 'TRES_GRAVE'] as $s): ?><option <?= ($filtre['gravite'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><button class="btn btn-outline-primary">Filtrer</button></div>
  <div class="col-md-2"><input class="form-control" type="date" name="du" value="<?= esc($filtre['du'] ?? '') ?>"></div><div class="col-md-2"><input class="form-control" type="date" name="au" value="<?= esc($filtre['au'] ?? '') ?>"></div>
</form>
<table class="table table-sm bg-white"><thead><tr><th>Décision</th><th>Employé</th><th>Département</th><th>Type</th><th>Jours</th><th>Gravité</th><th>Statut</th><th></th></tr></thead><tbody>
<?php foreach ($sanctions as $s): ?><tr><td><?= esc($s['date_decision']) ?></td><td><?= esc($s['matricule'] . ' ' . $s['nom']) ?></td><td><?= esc($s['departement']) ?></td><td><?= esc($s['type_sanction']) ?></td><td><?= (int) $s['nb_jours'] ?: '' ?></td><td><?= esc($s['gravite']) ?></td><td><?= esc($s['statut']) ?></td>
  <td><a href="<?= site_url('rh/discipline/incidents/' . (int) $s['id_incident']) ?>">Dossier</a></td></tr><?php endforeach; ?></tbody></table>
<?= $this->endSection() ?>
