<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3"><?= esc($titre) ?></h1>
<form method="post" action="<?= site_url('rh/formations/assigner') ?>" class="bg-white border rounded p-3"><?= csrf_field() ?>
  <select class="form-select mb-3" name="id_formation" required><option value="">Formation…</option><?php foreach ($formations as $f): ?><option value="<?= (int) $f['id_formation'] ?>"><?= esc($f['titre'] . ' (' . $f['date_debut'] . ')') ?></option><?php endforeach; ?></select>
  <div class="row"><?php foreach ($employes as $e): ?><div class="col-md-4"><label><input type="checkbox" name="employes[]" value="<?= (int) $e['id_employe'] ?>"> <?= esc($e['matricule'] . ' ' . $e['prenom'] . ' ' . $e['nom']) ?></label></div><?php endforeach; ?></div>
  <button class="btn btn-primary mt-3">Assigner</button>
</form>
<?= $this->endSection() ?>
