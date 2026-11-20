<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h3>Planifier un entretien</h3>
<p class="text-muted">Candidat : <strong><?= esc($cand['prenom'] . ' ' . $cand['nom']) ?></strong> — <?= esc($cand['titre']) ?></p>
<form method="post" action="<?= site_url('rh/entretiens/creer') ?>" class="card card-body">
  <?= csrf_field() ?>
  <input type="hidden" name="id_candidature" value="<?= (int) $cand['id_candidature'] ?>">
  <div class="mb-3">
    <label class="form-label">Date et heure</label>
    <input type="datetime-local" name="date_entretien" class="form-control" required value="<?= esc(old('date_entretien') ?? '') ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">Lieu</label>
    <input type="text" name="lieu" class="form-control" maxlength="255" value="<?= esc(old('lieu') ?? '') ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">Notes</label>
    <textarea name="notes" class="form-control" rows="3"><?= esc(old('notes') ?? '') ?></textarea>
  </div>
  <p class="small text-muted">Statut initial : PLANIFIE. L'organisateur est l'utilisateur connecté.</p>
  <button class="btn btn-primary">Planifier</button>
</form>
<?= $this->endSection() ?>
