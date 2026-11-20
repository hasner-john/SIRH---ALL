<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<?php $action = site_url('rh/evaluations/' . $cand['id_candidature'] . '/evaluer'); ?>
<a href="<?= site_url('rh/evaluations') ?>" class="small">&larr; Retour</a>
<h3 class="mt-2"><?= esc($cand['prenom'] . ' ' . $cand['nom']) ?>
  <span class="badge text-bg-secondary fs-6"><?= esc($cand['statut']) ?></span></h3>
<p class="text-muted"><?= esc($cand['titre']) ?> — <?= esc($cand['email']) ?> — <?= esc($cand['telephone'] ?? '') ?></p>

<div class="card mb-3"><div class="card-header">Évaluation du dossier</div><div class="card-body">
  <?= view('rh/evaluations/_form', ['action' => $action, 'type' => 'DOSSIER', 'ref' => null, 'eval' => $evals['DOSSIER-0'] ?? null]) ?>
</div></div>

<div class="card mb-3">
  <div class="card-header d-flex justify-content-between">
    <span>Entretiens</span>
    <?php if ($cand['statut'] === 'PRESELECTIONNEE'): ?>
      <a class="btn btn-sm btn-outline-primary" href="<?= site_url('rh/entretiens/nouveau/' . $cand['id_candidature']) ?>">+ Planifier</a>
    <?php endif; ?>
  </div>
  <div class="card-body">
  <?php foreach ($entretiens as $e): ?>
    <div class="border-bottom pb-2 mb-2">
      <strong><?= esc($e['date_entretien']) ?></strong> — <?= esc($e['lieu'] ?? '') ?>
      <span class="badge text-bg-secondary"><?= esc($e['statut']) ?></span>
      <?php if ($e['statut'] === 'REALISE'): ?>
        <div class="mt-2"><?= view('rh/evaluations/_form', [
            'action' => $action, 'type' => 'ENTRETIEN', 'ref' => (int) $e['id_entretien'],
            'eval' => $evals['ENTRETIEN-' . $e['id_entretien']] ?? null]) ?></div>
      <?php else: ?>
        <div class="small text-muted">Évaluation disponible une fois l'entretien réalisé.</div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  <?php if (! $entretiens): ?><span class="text-muted">Aucun entretien.</span><?php endif; ?>
  </div>
</div>

<div class="card mb-3">
  <div class="card-header d-flex justify-content-between">
    <span>Tests techniques</span>
    <?php if ($cand['statut'] === 'PRESELECTIONNEE'): ?>
      <a class="btn btn-sm btn-outline-primary" href="<?= site_url('rh/tests/nouveau/' . $cand['id_candidature']) ?>">+ Planifier</a>
    <?php endif; ?>
  </div>
  <div class="card-body">
  <?php foreach ($tests as $t): ?>
    <div class="border-bottom pb-2 mb-2">
      <strong><?= esc($t['intitule']) ?></strong> — <?= esc($t['date_test']) ?>
      <span class="badge text-bg-secondary"><?= esc($t['statut']) ?></span>
      <?php if ($t['statut'] === 'REALISE'): ?>
        <div class="mt-2"><?= view('rh/evaluations/_form', [
            'action' => $action, 'type' => 'TEST_TECHNIQUE', 'ref' => (int) $t['id_test'],
            'eval' => $evals['TEST_TECHNIQUE-' . $t['id_test']] ?? null]) ?></div>
      <?php else: ?>
        <div class="small text-muted">Évaluation disponible une fois le test réalisé.</div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  <?php if (! $tests): ?><span class="text-muted">Aucun test.</span><?php endif; ?>
  </div>
</div>

<?php if ($cand['statut'] === 'PRESELECTIONNEE'): ?>
<div class="card mb-3 border-primary"><div class="card-header">Décision de sélection</div><div class="card-body">
  <form method="post" action="<?= site_url('rh/evaluations/' . $cand['id_candidature'] . '/decider') ?>" class="row g-2">
    <?= csrf_field() ?>
    <div class="col-md-8"><input type="text" name="commentaire" class="form-control" placeholder="Commentaire (facultatif)"></div>
    <div class="col-md-2"><button name="decision" value="SELECTIONNEE" class="btn btn-success w-100"
         onclick="return confirm('Sélectionner ce candidat ?')">Sélectionner</button></div>
    <div class="col-md-2"><button name="decision" value="REFUSEE" class="btn btn-danger w-100"
         onclick="return confirm('Refuser cette candidature ?')">Refuser</button></div>
  </form>
</div></div>
<?php endif; ?>

<?php if ($historique): ?>
<div class="card"><div class="card-header">Historique des notes</div>
<table class="table table-sm mb-0">
  <thead><tr><th>Date</th><th>Type</th><th>Action</th><th>Ancienne/valeur enregistrée</th><th>Par</th></tr></thead>
  <tbody>
  <?php foreach ($historique as $h): $d = json_decode($h['donnees'], true) ?: []; ?>
    <tr>
      <td><?= esc($h['date_action']) ?></td>
      <td><?= esc($h['type_evaluation']) ?></td>
      <td><?= esc($h['action']) ?></td>
      <td>Note : <?= esc((string) ($d['note'] ?? '—')) ?> — <?= esc($d['commentaire'] ?? '') ?></td>
      <td><?= esc($h['email'] ?? '') ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
<?= $this->endSection() ?>
