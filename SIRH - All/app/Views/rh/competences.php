<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3"><?= esc($titre) ?></h1>
<div class="row g-4">
  <div class="col-lg-6">
    <h2 class="h5">Référentiel</h2>
    <form method="post" action="<?= site_url('rh/competences') ?>" class="row g-2 mb-3 bg-white border rounded p-2"><?= csrf_field() ?>
      <div class="col-4"><input class="form-control" name="code" placeholder="CODE" required></div>
      <div class="col-8"><input class="form-control" name="libelle" placeholder="Libellé" required></div>
      <div class="col-12"><input class="form-control" name="description" placeholder="Description"></div>
      <div class="col-auto"><label><input type="checkbox" name="actif" value="1" checked> actif</label></div>
      <div class="col-auto"><button class="btn btn-primary btn-sm">Ajouter</button></div>
    </form>
    <?php foreach ($competences as $c): ?>
    <form method="post" action="<?= site_url('rh/competences') ?>" class="row g-1 mb-1 align-items-center"><?= csrf_field() ?>
      <input type="hidden" name="id_competence" value="<?= (int) $c['id_competence'] ?>">
      <div class="col-3"><input class="form-control form-control-sm" name="code" value="<?= esc($c['code']) ?>"></div>
      <div class="col-4"><input class="form-control form-control-sm" name="libelle" value="<?= esc($c['libelle']) ?>"></div>
      <div class="col-3"><input class="form-control form-control-sm" name="description" value="<?= esc($c['description']) ?>"></div>
      <div class="col-1"><input type="checkbox" name="actif" value="1" <?= $c['actif'] ? 'checked' : '' ?>></div>
      <div class="col-1"><button class="btn btn-sm btn-outline-primary">OK</button></div>
    </form>
    <?php endforeach; ?>
  </div>
  <div class="col-lg-6">
    <h2 class="h5">Matrice poste × compétence</h2>
    <?php foreach ($postes as $p): ?>
    <div class="bg-white border rounded p-2 mb-2"><strong><?= esc($p['libelle']) ?></strong>
      <?php foreach ($parPoste[$p['id_poste']] ?? [] as $m): ?>
        <form method="post" action="<?= site_url('rh/competences/poste') ?>" class="d-flex align-items-center gap-2 small"><?= csrf_field() ?>
          <input type="hidden" name="id_poste" value="<?= (int) $p['id_poste'] ?>"><input type="hidden" name="id_competence" value="<?= (int) $m['id_competence'] ?>">
          <span class="flex-grow-1"><?= esc($m['code'] . ' — ' . $m['libelle']) ?></span>
          <input class="form-control form-control-sm" style="width:70px" type="number" min="1" max="5" name="niveau_attendu" value="<?= (int) $m['niveau_attendu'] ?>">
          <button class="btn btn-sm btn-outline-primary" name="action" value="lier">OK</button>
          <button class="btn btn-sm btn-outline-danger" name="action" value="retirer">Retirer</button>
        </form>
      <?php endforeach; ?>
      <form method="post" action="<?= site_url('rh/competences/poste') ?>" class="d-flex gap-2 mt-1"><?= csrf_field() ?>
        <input type="hidden" name="id_poste" value="<?= (int) $p['id_poste'] ?>">
        <select class="form-select form-select-sm" name="id_competence"><?php foreach ($competences as $c): ?><option value="<?= (int) $c['id_competence'] ?>"><?= esc($c['code']) ?></option><?php endforeach; ?></select>
        <input class="form-control form-control-sm" style="width:70px" type="number" min="1" max="5" name="niveau_attendu" value="3">
        <button class="btn btn-sm btn-primary" name="action" value="lier">Ajouter</button>
      </form>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?= $this->endSection() ?>
