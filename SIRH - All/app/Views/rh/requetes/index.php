<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h3>Candidats ayant envoyé des requêtes</h3>
<table class="table table-striped bg-white align-middle">
  <thead><tr><th>Candidat</th><th>Offre</th><th>Requêtes</th><th>À traiter</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($candidats as $c): ?>
    <tr>
      <td><?= esc($c['prenom'] . ' ' . $c['nom']) ?></td>
      <td><?= esc($c['titre']) ?></td>
      <td><?= (int) $c['total'] ?></td>
      <td><?php if ($c['a_traiter'] > 0): ?><span class="badge text-bg-warning"><?= (int) $c['a_traiter'] ?></span><?php else: ?>0<?php endif; ?></td>
      <td><a class="btn btn-sm btn-primary" href="<?= site_url('rh/requetes/' . $c['id_candidature']) ?>">Voir</a></td>
    </tr>
  <?php endforeach; ?>
  <?php if (! $candidats): ?><tr><td colspan="5" class="text-muted">Aucune requête.</td></tr><?php endif; ?>
  </tbody>
</table>
<?= $this->endSection() ?>
