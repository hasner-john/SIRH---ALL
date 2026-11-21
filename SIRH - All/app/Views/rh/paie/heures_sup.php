<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3"><?= esc($titre) ?></h1>
<form method="post" action="<?= site_url('rh/paie/heures-supplementaires') ?>" class="row g-2 mb-2 border rounded p-3 bg-white">
  <?= csrf_field() ?>
  <div class="col-md-3"><select class="form-select" name="id_employe" required><option value="">Employé…</option>
    <?php foreach ($employes as $e): ?><option value="<?= (int) $e['id_employe'] ?>"><?= esc($e['matricule'] . ' — ' . $e['prenom'] . ' ' . $e['nom']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><input class="form-control" type="date" name="date_travail" id="hs_date" required></div>
  <div class="col-md-2"><input class="form-control" type="time" name="heure_debut" id="hs_debut" required></div>
  <div class="col-md-2"><input class="form-control" type="time" name="heure_fin" id="hs_fin" required></div>
  <div class="col-md-3"><input class="form-control" name="motif" placeholder="Motif"></div>
  <div class="col-12"><button class="btn btn-primary">Enregistrer</button></div>
</form>
<p id="hs_apercu" class="text-muted small mb-4">Aperçu : saisissez la date et les heures.</p>
<form method="get" class="row g-2 mb-3">
  <div class="col-md-3"><select class="form-select" name="id_employe"><option value="">Tous</option>
    <?php foreach ($employes as $e): ?><option value="<?= (int) $e['id_employe'] ?>" <?= ((int) ($filtre['id_employe'] ?? 0)) === (int) $e['id_employe'] ? 'selected' : '' ?>><?= esc($e['matricule'] . ' ' . $e['nom']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><select class="form-select" name="statut"><option value="">Tous statuts</option>
    <?php foreach (['A_VALIDER', 'VALIDE', 'REFUSEE'] as $s): ?><option <?= ($filtre['statut'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></div>
  <div class="col-md-auto"><button class="btn btn-outline-primary">Filtrer</button></div>
</form>
<div class="table-responsive"><table class="table table-sm bg-white">
  <thead><tr><th>Date</th><th>Employé</th><th>Heures</th><th>Jour/Nuit</th><th>Motif</th><th>Statut</th><th></th></tr></thead>
  <tbody><?php foreach ($heures as $h): ?><tr>
    <td><?= esc($h['date_travail']) ?><?= date('N', strtotime($h['date_travail'])) === '7' ? ' <span class="badge text-bg-info">dimanche</span>' : '' ?></td>
    <td><?= esc($h['matricule'] . ' ' . $h['nom']) ?></td><td><?= esc($h['heures']) ?></td><td><?= $h['nuit'] ? 'Nuit' : 'Jour' ?></td>
    <td><?= esc($h['motif']) ?></td><td><?= esc($h['statut']) ?></td>
    <td><?php if ($h['statut'] === 'A_VALIDER'): ?>
      <form method="post" action="<?= site_url('rh/paie/heures-supplementaires/' . (int) $h['id_heure_supplementaire'] . '/decision') ?>" class="d-inline"><?= csrf_field() ?>
        <button name="decision" value="VALIDE" class="btn btn-sm btn-success">Valider</button>
        <button name="decision" value="REFUSEE" class="btn btn-sm btn-outline-danger">Refuser</button></form>
    <?php endif; ?></td></tr><?php endforeach; ?></tbody>
</table></div>
<script>
(function () {
  const d = document.getElementById('hs_date'), a = document.getElementById('hs_debut'), f = document.getElementById('hs_fin'), o = document.getElementById('hs_apercu');
  function maj() {
    if (!d.value || !a.value || !f.value) return;
    fetch('<?= site_url('rh/paie/heures-supplementaires/apercu') ?>?date=' + d.value + '&debut=' + a.value + '&fin=' + f.value)
      .then(r => r.json()).then(j => {
        o.textContent = j.erreur ? j.erreur : 'Dimanche : ' + (j.dimanche ? 'oui' : 'non') + ' — Nuit : ' + j.nuit + ' h — Total : ' + j.total + ' h — Coefficient estimé ×' + j.coefficient;
      });
  }
  [d, a, f].forEach(x => x.addEventListener('change', maj));
})();
</script>
<?= $this->endSection() ?>
