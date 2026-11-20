<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h3>Entretiens</h3>
<table class="table table-striped bg-white align-middle">
  <thead><tr><th>Candidat</th><th>Offre</th><th>Date</th><th>Lieu</th><th>Statut</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($entretiens as $e): ?>
    <tr>
      <td><?= esc($e['prenom'] . ' ' . $e['nom']) ?></td>
      <td><?= esc($e['titre']) ?></td>
      <td><?= esc($e['date_entretien']) ?></td>
      <td><?= esc($e['lieu'] ?? '') ?></td>
      <td><span class="badge text-bg-secondary"><?= esc($e['statut']) ?></span></td>
      <td>
        <?php if ($e['statut'] === 'PLANIFIE'): ?>
          <form method="post" action="<?= site_url('rh/entretiens/' . $e['id_entretien'] . '/statut') ?>" class="d-inline">
            <?= csrf_field() ?><input type="hidden" name="statut" value="REALISE">
            <button class="btn btn-sm btn-success">Marquer réalisé</button>
          </form>
          <form method="post" action="<?= site_url('rh/entretiens/' . $e['id_entretien'] . '/statut') ?>" class="d-inline">
            <?= csrf_field() ?><input type="hidden" name="statut" value="ANNULE">
            <button class="btn btn-sm btn-outline-danger">Annuler</button>
          </form>
        <?php elseif ($e['statut'] === 'REALISE'): ?>
          <!-- Le bouton d'évaluation n'apparaît que si l'entretien est REALISE -->
          <a class="btn btn-sm btn-primary" href="<?= site_url('rh/evaluations/' . $e['id_candidature']) ?>">Évaluer</a>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (! $entretiens): ?><tr><td colspan="6" class="text-muted">Aucun entretien.</td></tr><?php endif; ?>
  </tbody>
</table>
<?= $this->endSection() ?>
