<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h3>Planifier un test technique</h3>
<p class="text-muted">Candidat : <strong><?= esc($cand['prenom'] . ' ' . $cand['nom']) ?></strong> — <?= esc($cand['titre']) ?></p>
<form method="post" action="<?= site_url('rh/tests/creer') ?>" class="card card-body">
  <?= csrf_field() ?>
  <input type="hidden" name="id_candidature" value="<?= (int) $cand['id_candidature'] ?>">
  <div class="mb-3">
    <label class="form-label">Intitulé</label>
    <input type="text" name="intitule" class="form-control" maxlength="180" required value="<?= esc(old('intitule') ?? '') ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">Description</label>
    <textarea name="description" class="form-control" rows="3"><?= esc(old('description') ?? '') ?></textarea>
  </div>
  <div class="mb-3">
    <label class="form-label">Date et heure</label>
    <input type="datetime-local" name="date_test" class="form-control" required value="<?= esc(old('date_test') ?? '') ?>">
  </div>
  <button class="btn btn-primary">Planifier</button>
</form>
<?= $this->endSection() ?>
