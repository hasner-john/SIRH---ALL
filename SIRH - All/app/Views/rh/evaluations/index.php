<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h3>Candidatures à évaluer</h3>
<table class="table table-striped bg-white align-middle">
  <thead><tr><th>Candidat</th><th>Offre</th><th>Statut</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($candidatures as $c): ?>
    <tr>
      <td><?= esc($c['prenom'] . ' ' . $c['nom']) ?></td>
      <td><?= esc($c['titre']) ?></td>
      <td><span class="badge text-bg-secondary"><?= esc($c['statut']) ?></span></td>
      <td><a class="btn btn-sm btn-primary" href="<?= site_url('rh/evaluations/' . $c['id_candidature']) ?>">Ouvrir</a></td>
    </tr>
  <?php endforeach; ?>
  <?php if (! $candidatures): ?><tr><td colspan="4" class="text-muted">Aucune candidature présélectionnée.</td></tr><?php endif; ?>
  </tbody>
</table>
<?= $this->endSection() ?>
