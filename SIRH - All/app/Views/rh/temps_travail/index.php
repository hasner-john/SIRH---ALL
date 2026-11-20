<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3">Temps de travail et pointage</h1>
<form method="post" action="<?= site_url('rh/temps-travail/pointage') ?>" class="row g-2 mb-4">
  <?= csrf_field() ?>
  <div class="col-md-2"><input name="id_employe" type="number" class="form-control" placeholder="ID salarié" required></div>
  <div class="col-md-2"><input name="date_pointage" type="date" class="form-control" required></div>
  <div class="col-md-3"><input name="heure_entree" type="datetime-local" class="form-control"></div>
  <div class="col-md-3"><input name="heure_sortie" type="datetime-local" class="form-control"></div>
  <div class="col-md-2"><button class="btn btn-primary w-100">Enregistrer</button></div>
</form>
<table class="table table-sm table-striped">
  <thead><tr><th>Salarié</th><th>Date</th><th>Entrée</th><th>Sortie</th><th>Statut</th></tr></thead>
  <tbody><?php foreach ($pointages as $pointage): ?>
    <tr><td><?= esc($pointage['id_employe']) ?></td><td><?= esc($pointage['date_pointage']) ?></td>
      <td><?= esc($pointage['heure_entree'] ?? '-') ?></td><td><?= esc($pointage['heure_sortie'] ?? '-') ?></td>
      <td><?= esc($pointage['statut']) ?></td></tr>
  <?php endforeach; ?></tbody>
</table>
<?= $this->endSection() ?>
