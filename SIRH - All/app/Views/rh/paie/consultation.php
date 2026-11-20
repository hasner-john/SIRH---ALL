<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3">État de paie au <?= esc(date('d/m/Y', strtotime($date))) ?></h1>
<a href="<?= site_url('rh/modules-avances') ?>" class="btn btn-sm btn-outline-secondary mb-3">← Retour</a>

<?php if (! $periode || ! $bulletins): ?>
  <div class="alert alert-warning">Aucune paie calculée pour cette date.</div>
<?php else: ?>
  <p>Période <strong><?= sprintf('%02d/%04d', $periode['mois'], $periode['annee']) ?></strong>
     — statut <span class="badge text-bg-secondary"><?= esc($periode['statut']) ?></span>
     <a class="btn btn-sm btn-success ms-2" href="<?= site_url('rh/modules-avances/paie/etat/' . (int) $periode['id_periode']) ?>">Exporter Excel</a></p>
  <?php $cols = ['salaire_base','retenue_absence','base_mois','indemnites','majorations','salaire_brut','cnaps_salarial','ostie_salarial','irsa','total_retenues','net_a_payer'];
        $tot = array_fill_keys($cols, 0.0); $f = fn($v) => number_format((float) $v, 0, ',', ' '); ?>
  <div class="table-responsive"><table class="table table-sm table-bordered">
    <thead><tr><th>Matr.</th><th>Nom et prénoms</th><th>Base</th><th>Ret. abs.</th><th>Base mois</th><th>Indemn.</th>
      <th>HS/Major.</th><th>Brut</th><th>CNaPS</th><th>OSTIE</th><th>IRSA</th><th>Total ret.</th><th>Net à payer</th><th>Fiche</th></tr></thead>
    <tbody>
    <?php foreach ($bulletins as $b): foreach ($cols as $c) { $tot[$c] += (float) $b[$c]; } ?>
      <tr><td><?= esc($b['matricule']) ?></td><td><?= esc($b['nom'] . ' ' . $b['prenom']) ?></td>
      <?php foreach ($cols as $c): ?><td class="text-end"><?= $f($b[$c]) ?></td><?php endforeach; ?>
      <td><a target="_blank" href="<?= site_url('rh/modules-avances/paie/bulletin/' . (int) $b['id_bulletin']) ?>">PDF</a></td></tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot><tr class="fw-bold"><td colspan="2">TOTAL GÉNÉRAL</td>
      <?php foreach ($cols as $c): ?><td class="text-end"><?= $f($tot[$c]) ?></td><?php endforeach; ?><td></td></tr></tfoot>
  </table></div>
<?php endif; ?>
<?= $this->endSection() ?>