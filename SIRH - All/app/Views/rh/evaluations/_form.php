<?php /** Variables : $action, $type, $ref (int|null), $eval (array|null) */ ?>
<form method="post" action="<?= $action ?>" class="row g-2 align-items-end">
  <?= csrf_field() ?>
  <input type="hidden" name="type_evaluation" value="<?= esc($type) ?>">
  <input type="hidden" name="ref" value="<?= esc((string) ($ref ?? '')) ?>">
  <div class="col-md-2">
    <label class="form-label small mb-0">Note (0–10)</label>
    <input type="number" name="note" step="0.25" min="0" max="10" class="form-control form-control-sm"
           value="<?= esc((string) ($eval['note'] ?? '')) ?>">
  </div>
  <div class="col-md-7">
    <label class="form-label small mb-0">Commentaire</label>
    <input type="text" name="commentaire" class="form-control form-control-sm" value="<?= esc($eval['commentaire'] ?? '') ?>">
  </div>
  <div class="col-md-3">
    <button class="btn btn-sm btn-primary w-100"><?= $eval ? 'Modifier' : 'Enregistrer' ?></button>
  </div>
  <?php if ($eval): ?>
    <div class="col-12 small text-muted">Dernière évaluation par <?= esc($eval['evaluateur']) ?> le <?= esc($eval['date_evaluation']) ?></div>
  <?php endif; ?>
</form>
