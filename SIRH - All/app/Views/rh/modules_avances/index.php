<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-4"><?= esc($titre) ?></h1>

<div class="d-flex flex-wrap gap-2 mb-4">
  <a class="btn btn-primary" href="<?= site_url('rh/paie/periodes') ?>">Périodes de paie</a>
  <a class="btn btn-outline-primary" href="<?= site_url('rh/paie/variables') ?>">Variables / indemnités</a>
  <a class="btn btn-outline-primary" href="<?= site_url('rh/paie/heures-supplementaires') ?>">Heures supplémentaires</a>
  <a class="btn btn-outline-primary" href="<?= site_url('rh/temps-travail/retards') ?>">Retards</a>
  <a class="btn btn-outline-secondary" href="<?= site_url('rh/parametres') ?>">Paramètres RH</a>
</div>

<form method="get" action="<?= site_url('rh/modules-avances/paie/consulter') ?>" class="row g-2 mb-3">
  <div class="col-md-3"><input class="form-control" type="date" name="date" required></div>
  <div class="col-md-auto"><button class="btn btn-outline-primary">Voir l'état de paie à cette date</button></div>
  <div class="col-md-3"><select class="form-select" id="periode_export"><?php foreach ($periodes as $p): ?><option value="<?= (int) $p['id_periode'] ?>"><?= sprintf('%02d/%04d — %s', $p['mois'], $p['annee'], $p['statut']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-auto"><a class="btn btn-outline-secondary" href="#" onclick="location='<?= site_url('rh/modules-avances/paie/etat') ?>/'+document.getElementById('periode_export').value;return false;">Exporter l'état de paie (Excel)</a></div>
</form>

<div class="table-responsive"><table class="table table-sm bg-white">
  <thead><tr><th>Période</th><th>Matricule</th><th>Salarié</th><th>Jours</th><th>Brut</th><th>CNaPS</th><th>OSTIE</th><th>IRSA</th><th>Net à payer</th><th>Statut</th><th>STC</th><th>PDF</th></tr></thead>
  <tbody><?php foreach ($bulletins as $b): ?><tr>
    <td><?= sprintf('%02d/%04d', $b['mois'], $b['annee']) ?></td>
    <td><?= esc($b['matricule']) ?></td><td><?= esc($b['prenom'] . ' ' . $b['nom']) ?></td><td><?= esc($b['jours_travailles']) ?>/30</td>
    <td><?= number_format((float) $b['salaire_brut'], 0, ',', ' ') ?></td><td><?= number_format((float) $b['cnaps_salarial'], 0, ',', ' ') ?></td>
    <td><?= number_format((float) $b['ostie_salarial'], 0, ',', ' ') ?></td><td><?= number_format((float) $b['irsa'], 0, ',', ' ') ?></td>
    <td><strong><?= number_format((float) $b['net_a_payer'], 0, ',', ' ') ?> Ar</strong></td><td><?= esc($b['statut']) ?></td>
    <td><?= (int) $b['inclus_stc'] ? 'oui' : '' ?></td>
    <td><a href="<?= site_url('rh/modules-avances/paie/bulletin/' . (int) $b['id_bulletin']) ?>" target="_blank">PDF</a></td>
  </tr><?php endforeach; ?></tbody>
</table></div>
<?= $this->endSection() ?>
