<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3"><?= esc($titre) ?></h1>
<form method="post" action="<?= site_url('rh/temps-travail/retards') ?>" class="row g-2 mb-4 border rounded p-3 bg-white">
  <?= csrf_field() ?>
  <div class="col-md-3"><select class="form-select" name="id_employe" required><option value="">Employé…</option>
    <?php foreach ($employes as $e): ?><option value="<?= (int) $e['id_employe'] ?>"><?= esc($e['matricule'] . ' — ' . $e['prenom'] . ' ' . $e['nom']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><input class="form-control" type="date" name="date_pointage" required></div>
  <div class="col-md-2"><input class="form-control" type="number" min="1" name="minutes" placeholder="Minutes"></div>
  <div class="col-md-2"><input class="form-control" type="time" name="heure_arrivee" title="ou heure d'arrivée réelle"></div>
  <div class="col-md-3"><input class="form-control" name="motif" placeholder="Motif"></div>
  <div class="col-md-auto form-check ms-2 mt-2"><input class="form-check-input" type="checkbox" name="justifie" value="1" id="j"><label class="form-check-label" for="j">Justifié</label></div>
  <div class="col-md-auto"><button class="btn btn-primary">Enregistrer le retard</button></div>
</form>
<div class="table-responsive"><table class="table table-sm bg-white">
  <thead><tr><th>Date</th><th>Employé</th><th>Minutes</th><th>Motif / justificatif</th><th>Origine</th><th>Statut</th><th>Déduit ?</th><th>Actions</th></tr></thead>
  <tbody><?php foreach ($retards as $r): ?><tr>
    <td><?= esc($r['date_pointage']) ?></td><td><?= esc($r['matricule'] . ' ' . $r['nom']) ?></td><td><?= (int) $r['minutes'] ?></td>
    <td><?= esc($r['motif']) ?><?= $r['justificatif'] ? '<br><small class="text-success">' . esc($r['justificatif']) . '</small>' : '' ?></td>
    <td><?= $r['automatique'] ? 'Pointage' : 'Manuel' ?></td><td><?= esc($r['statut']) ?></td>
    <td><?= $r['justifie'] ? 'non' : 'oui' ?></td>
    <td>
      <?php if ($r['statut'] === 'A_TRAITER'): ?>
      <form method="post" action="<?= site_url('rh/temps-travail/retards/' . (int) $r['id_retard'] . '/justifier') ?>" class="d-flex gap-1 mb-1"><?= csrf_field() ?>
        <input class="form-control form-control-sm" name="justificatif" placeholder="Justificatif" required><button class="btn btn-sm btn-outline-primary">Justifier</button></form>
      <?php endif; ?>
      <?php if ($r['statut'] !== 'VALIDE'): ?>
      <form method="post" action="<?= site_url('rh/temps-travail/retards/' . (int) $r['id_retard'] . '/valider') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-success">Valider</button></form>
      <?php endif; ?>
    </td></tr><?php endforeach; ?></tbody>
</table></div>
<?= $this->endSection() ?>
