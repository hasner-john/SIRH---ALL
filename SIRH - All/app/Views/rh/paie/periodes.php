<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3"><?= esc($titre) ?></h1>
<form method="post" action="<?= site_url('rh/paie/periodes') ?>" class="row g-2 mb-4 border rounded p-3 bg-white">
  <?= csrf_field() ?>
  <div class="col-md-2"><input class="form-control" type="number" name="mois" min="1" max="12" placeholder="Mois" value="<?= date('n') ?>" required></div>
  <div class="col-md-2"><input class="form-control" type="number" name="annee" min="2000" placeholder="Année" value="<?= date('Y') ?>" required></div>
  <div class="col-md-3"><input class="form-control" type="date" name="date_paiement" title="Date de paiement (défaut : dernier jour du mois)"></div>
  <div class="col-md-auto"><button class="btn btn-primary">Ouvrir une période</button></div>
</form>
<div class="table-responsive"><table class="table table-sm bg-white">
  <thead><tr><th>Période</th><th>Paiement</th><th>Statut</th><th>Bulletins</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($periodes as $p): $s = $p['statut']; ?>
    <tr>
      <td><?= sprintf('%02d/%04d', $p['mois'], $p['annee']) ?></td><td><?= esc($p['date_paiement']) ?></td>
      <td><span class="badge text-bg-secondary"><?= esc($s) ?></span></td>
      <td><?= (int) $p['nb_bulletins'] ?> (<?= (int) $p['nb_payes'] ?> payés)</td>
      <td>
        <form method="post" action="<?= site_url('rh/paie/periodes/' . (int) $p['id_periode'] . '/statut') ?>" class="d-inline">
          <?= csrf_field() ?>
          <?php if (in_array($s, ['OUVERT', 'CALCUL'], true)): ?><button name="action" value="calculer" class="btn btn-sm btn-primary"><?= $s === 'CALCUL' ? 'Recalculer' : 'Calculer' ?></button><?php endif; ?>
          <?php if ($s === 'CALCUL'): ?><button name="action" value="valider" class="btn btn-sm btn-success">Valider</button><?php endif; ?>
          <?php if ($s === 'VALIDE'): ?><button name="action" value="payer" class="btn btn-sm btn-warning">Marquer payée</button><?php endif; ?>
          <?php if ($s === 'PAIE'): ?><button name="action" value="cloturer" class="btn btn-sm btn-dark">Clôturer</button><?php endif; ?>
          <?php if (in_array($s, ['OUVERT', 'CALCUL'], true)): ?><button name="action" value="annuler" class="btn btn-sm btn-outline-danger" onclick="return confirm('Annuler cette période ?')">Annuler</button><?php endif; ?>
        </form>
        <?php if ((int) $p['nb_bulletins'] > 0): ?>
          <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('rh/modules-avances/paie/etat/' . (int) $p['id_periode']) ?>">Excel</a>
        <?php endif; ?>
        <?php if (in_array($s, ['VALIDE', 'PAIE', 'CLOTURE'], true)): ?><span class="small text-muted ms-1">verrouillée : correction par RAPPEL le mois suivant</span><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?= $this->endSection() ?>
