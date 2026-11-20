<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h3>Tests techniques</h3>
<table class="table table-striped bg-white align-middle">
  <thead><tr><th>Candidat</th><th>Offre</th><th>Intitulé</th><th>Date</th><th>Statut</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($tests as $t): ?>
    <tr>
      <td><?= esc($t['prenom'] . ' ' . $t['nom']) ?></td>
      <td><?= esc($t['titre']) ?></td>
      <td><?= esc($t['intitule']) ?></td>
      <td><?= esc($t['date_test']) ?></td>
      <td><span class="badge text-bg-secondary"><?= esc($t['statut']) ?></span></td>
      <td>
        <?php if ($t['statut'] === 'PLANIFIE'): ?>
          <form method="post" action="<?= site_url('rh/tests/' . $t['id_test'] . '/statut') ?>" class="d-inline">
            <?= csrf_field() ?><input type="hidden" name="statut" value="REALISE">
            <button class="btn btn-sm btn-success">Marquer réalisé</button>
          </form>
          <form method="post" action="<?= site_url('rh/tests/' . $t['id_test'] . '/statut') ?>" class="d-inline">
            <?= csrf_field() ?><input type="hidden" name="statut" value="ANNULE">
            <button class="btn btn-sm btn-outline-danger">Annuler</button>
          </form>
        <?php elseif ($t['statut'] === 'REALISE'): ?>
          <a class="btn btn-sm btn-primary" href="<?= site_url('rh/evaluations/' . $t['id_candidature']) ?>">Évaluer</a>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (! $tests): ?><tr><td colspan="6" class="text-muted">Aucun test.</td></tr><?php endif; ?>
  </tbody>
</table>
<?= $this->endSection() ?>
