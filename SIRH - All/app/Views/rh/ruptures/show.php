<?= $this->extend($direction ? 'direction/_layout' : 'rh/layout') ?>
<?= $this->section('content') ?>
<?php if ($direction): ?><link href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>" rel="stylesheet"><?php endif; ?>
<h1 class="h3 mb-3"><?= esc($titre) ?> — <?= esc($r['matricule'] . ' ' . $r['prenom'] . ' ' . $r['nom']) ?></h1>
<p><?= esc($r['motif']) ?> — notification <?= esc($r['date_notification']) ?> — sortie <strong><?= esc($r['date_sortie']) ?></strong> (préavis <?= (int) $r['duree_preavis_jours'] ?> j) — <span class="badge text-bg-secondary"><?= esc($r['statut']) ?></span> <?= esc($r['motif_detail']) ?></p>
<form method="post" action="<?= site_url(($direction ? 'direction' : 'rh') . '/ruptures/' . (int) $r['id_rupture'] . '/statut') ?>" class="mb-4"><?= csrf_field() ?>
  <?php if (! $direction): ?><button class="btn btn-sm btn-outline-primary" name="statut" value="EN_COURS">En cours</button> <?php endif; ?>
  <button class="btn btn-sm btn-success" name="statut" value="VALIDEE">Valider</button>
  <?php if (! $direction): ?><button class="btn btn-sm btn-dark" name="statut" value="CLOTURE">Clôturer</button> <button class="btn btn-sm btn-outline-danger" name="statut" value="ANNULEE">Annuler</button><?php endif; ?></form>
<?php if (! $direction): ?>
<div class="row g-4"><div class="col-lg-6">
<h2 class="h5">Solde de tout compte</h2>
<?php if ($stc): $edit = $stc['statut'] === 'BROUILLON'; ?>
<form method="post" action="<?= site_url('rh/ruptures/' . (int) $r['id_rupture'] . '/stc/calculer') ?>" class="bg-white border rounded p-3"><?= csrf_field() ?><input type="hidden" name="manuel" value="1">
  <table class="table table-sm mb-2"><tbody>
    <tr><td>Dernier salaire (base)</td><td class="text-end"><?= number_format((float) $stc['dernier_salaire'], 0, ',', ' ') ?></td></tr>
    <?php foreach (['salaire_proratise' => 'Salaire proratisé', 'indemnite_conges' => 'Indemnité de congés (' . $stc['jours_conges_restants'] . ' j)', 'indemnite_rupture' => 'Indemnité de rupture', 'avances' => '− Avances', 'retenues' => '− Retenues'] as $k => $l): ?>
    <tr><td><?= esc($l) ?></td><td><input class="form-control form-control-sm text-end" type="number" step="0.01" min="0" name="<?= $k ?>" value="<?= esc($stc[$k]) ?>" <?= $edit ? '' : 'readonly' ?>></td></tr><?php endforeach; ?>
    <tr class="fw-bold"><td>Net à payer</td><td class="text-end"><?= number_format((float) $stc['montant_net'], 0, ',', ' ') ?> Ar</td></tr></tbody></table>
  <span class="badge text-bg-secondary"><?= esc($stc['statut']) ?></span>
  <?php if ($edit): ?><button class="btn btn-sm btn-primary">Enregistrer les montants</button><?php endif; ?>
</form>
<div class="mt-2 d-flex gap-2 flex-wrap">
  <?php if ($edit): ?><form method="post" action="<?= site_url('rh/ruptures/' . (int) $r['id_rupture'] . '/stc/calculer') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary">Recalculer</button></form><?php endif; ?>
  <form method="post" action="<?= site_url('rh/ruptures/' . (int) $r['id_rupture'] . '/stc/statut') ?>" class="d-flex gap-2"><?= csrf_field() ?>
    <?php if ($edit): ?><button class="btn btn-sm btn-success" name="statut" value="VALIDE">Valider</button><?php endif; ?>
    <?php if ($stc['statut'] === 'VALIDE'): ?><button class="btn btn-sm btn-warning" name="statut" value="PAIE">Marquer payé</button><?php endif; ?></form>
  <a class="btn btn-sm btn-outline-primary" target="_blank" href="<?= site_url('rh/ruptures/' . (int) $r['id_rupture'] . '/stc/pdf') ?>">PDF du STC</a></div>
<?php endif; ?>
</div><div class="col-lg-6">
<h2 class="h5">Checklist</h2>
<?php foreach ($checklist as $c): ?>
<form method="post" action="<?= site_url('rh/ruptures/' . (int) $r['id_rupture'] . '/checklist/' . (int) $c['id_checklist']) ?>" class="d-flex gap-1 align-items-center mb-1"><?= csrf_field() ?>
  <span style="width:42%"><?= esc($c['element']) ?></span>
  <select class="form-select form-select-sm" name="statut"><?php foreach (['A_FAIRE', 'EN_COURS', 'TERMINE', 'NON_APPLICABLE'] as $s): ?><option <?= $c['valeur_statut'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select>
  <input class="form-control form-control-sm" name="commentaire" value="<?= esc($c['commentaire']) ?>" placeholder="Commentaire"><button class="btn btn-sm btn-outline-primary">OK</button></form><?php endforeach; ?>
<h2 class="h5 mt-4">Attestations</h2>
<?php foreach ($attestations as $a): ?><div class="d-flex justify-content-between border-bottom py-1"><span><?= esc($a['type_attestation'] . ' — ' . $a['reference']) ?><?= $a['pdf_nom'] ? ' <small class="text-success">généré</small>' : '' ?></span>
  <a class="btn btn-sm btn-outline-primary" target="_blank" href="<?= site_url('rh/ruptures/' . (int) $r['id_rupture'] . '/attestations/' . (int) $a['id_attestation'] . '/pdf') ?>"><?= $a['pdf_nom'] ? 'Régénérer / ouvrir' : 'Générer' ?></a></div><?php endforeach; ?>
</div></div>
<?php endif; ?>
<?= $this->endSection() ?>
