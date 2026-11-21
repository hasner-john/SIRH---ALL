<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3"><?= esc($titre) ?></h1>
<p><?= esc($f['date_debut'] . ' → ' . $f['date_fin']) ?> — <?= esc($f['prestataire']) ?> — compétence : <?= esc($f['competence'] ?? '—') ?> — <span class="badge text-bg-secondary"><?= esc($f['statut']) ?></span></p>
<form method="post" action="<?= site_url('rh/formations/' . (int) $f['id_formation'] . '/statut') ?>" class="mb-3"><?= csrf_field() ?>
  <?php foreach (['EN_COURS' => 'Démarrer', 'TERMINEE' => 'Terminer', 'ANNULEE' => 'Annuler'] as $s => $l): ?><button class="btn btn-sm btn-outline-primary" name="statut" value="<?= $s ?>"><?= $l ?></button> <?php endforeach; ?></form>
<table class="table table-sm bg-white"><thead><tr><th>Participant</th><th>Statut</th><th>Niveau avant</th><th>Niveau après</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($parts as $p): ?><tr><td><?= esc($p['matricule'] . ' ' . $p['prenom'] . ' ' . $p['nom']) ?></td><td><?= esc($p['statut']) ?></td><td><?= esc($p['niveau_avant'] ?? '—') ?></td><td><?= esc($p['niveau_apres'] ?? '—') ?></td>
  <td>
    <form method="post" action="<?= site_url('rh/formations/' . (int) $f['id_formation'] . '/participants/' . (int) $p['id_participant'] . '/statut') ?>" class="d-inline"><?= csrf_field() ?>
      <?php foreach (['PRESENT', 'TERMINE', 'ABANDON'] as $s): ?><button class="btn btn-sm btn-outline-secondary" name="statut" value="<?= $s ?>"><?= $s ?></button> <?php endforeach; ?></form>
    <?php if ($f['statut'] === 'TERMINEE' && $p['statut'] === 'TERMINE' && $p['niveau_apres'] === null): ?>
    <form method="post" action="<?= site_url('rh/formations/' . (int) $f['id_formation'] . '/niveau-apres') ?>" class="d-inline-flex gap-1"><?= csrf_field() ?>
      <input type="hidden" name="id_employe" value="<?= (int) $p['id_employe'] ?>"><input class="form-control form-control-sm" style="width:70px" type="number" min="1" max="5" name="niveau_apres" required><button class="btn btn-sm btn-success">Niveau après</button></form>
    <?php endif; ?></td></tr><?php endforeach; ?></tbody></table>
<?= $this->endSection() ?>
