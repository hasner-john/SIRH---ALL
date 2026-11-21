<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3"><?= esc($titre) ?></h1>
<form method="post" action="<?= site_url('rh/paie/variables') ?>" class="row g-2 mb-4 border rounded p-3 bg-white">
  <?= csrf_field() ?>
  <div class="col-md-3"><select class="form-select" name="id_employe" required><option value="">Employé…</option>
    <?php foreach ($employes as $e): ?><option value="<?= (int) $e['id_employe'] ?>"><?= esc($e['matricule'] . ' — ' . $e['prenom'] . ' ' . $e['nom']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><select class="form-select" name="id_periode" required><option value="">Période…</option>
    <?php foreach ($periodes as $p): ?><option value="<?= (int) $p['id_periode'] ?>"><?= sprintf('%02d/%04d (%s)', $p['mois'], $p['annee'], $p['statut']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><select class="form-select" name="id_rubrique" id="rubrique" required><option value="">Rubrique…</option>
    <?php foreach ($rubriques as $r): ?><option value="<?= (int) $r['id_rubrique'] ?>" data-cat="<?= esc($r['categorie']) ?>"><?= esc($r['categorie'] . ' — ' . $r['libelle']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-1"><input class="form-control" type="number" step="0.01" name="nombre" value="1" title="Nombre"></div>
  <div class="col-md-2"><input class="form-control" type="number" step="0.01" min="0" name="montant" placeholder="Montant (Ar)" required></div>
  <div class="col-md-9"><input class="form-control" name="motif" placeholder="Motif"></div>
  <div class="col-md-auto"><button class="btn btn-primary">Enregistrer (ajoute ou modifie)</button></div>
  <div class="col-md-auto"><a class="btn btn-outline-secondary" href="<?= site_url('rh/paie/variables?categorie=INDEMNITE') ?>">Raccourci : indemnités</a></div>
</form>
<form method="get" class="row g-2 mb-3">
  <div class="col-md-3"><select class="form-select" name="id_periode"><option value="">Toutes les périodes</option>
    <?php foreach ($toutesPeriodes as $p): ?><option value="<?= (int) $p['id_periode'] ?>" <?= ((int) ($filtre['id_periode'] ?? 0)) === (int) $p['id_periode'] ? 'selected' : '' ?>><?= sprintf('%02d/%04d', $p['mois'], $p['annee']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><select class="form-select" name="id_employe"><option value="">Tous les employés</option>
    <?php foreach ($employes as $e): ?><option value="<?= (int) $e['id_employe'] ?>" <?= ((int) ($filtre['id_employe'] ?? 0)) === (int) $e['id_employe'] ? 'selected' : '' ?>><?= esc($e['matricule'] . ' ' . $e['nom']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><select class="form-select" name="categorie"><option value="">Toutes catégories</option>
    <?php foreach (['INDEMNITE', 'PRIME', 'RAPPEL', 'AVANCE'] as $c): ?><option <?= ($filtre['categorie'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option><?php endforeach; ?></select></div>
  <div class="col-md-auto"><button class="btn btn-outline-primary">Filtrer</button></div>
</form>
<div class="table-responsive"><table class="table table-sm bg-white">
  <thead><tr><th>Période</th><th>Employé</th><th>Rubrique</th><th>Cat.</th><th>Montant</th><th>Motif</th><th></th></tr></thead>
  <tbody><?php foreach ($variables as $v): ?><tr>
    <td><?= sprintf('%02d/%04d', $v['mois'], $v['annee']) ?> <small class="text-muted"><?= esc($v['statut_periode']) ?></small></td>
    <td><?= esc($v['matricule'] . ' ' . $v['nom']) ?></td><td><?= esc($v['rubrique']) ?></td><td><?= esc($v['categorie']) ?></td>
    <td class="text-end"><?= number_format((float) $v['montant'], 0, ',', ' ') ?></td><td><?= esc($v['motif']) ?></td>
    <td><?php if (in_array($v['statut_periode'], ['OUVERT', 'CALCUL'], true)): ?>
      <form method="post" action="<?= site_url('rh/paie/variables/' . (int) $v['id_variable'] . '/supprimer') ?>" onsubmit="return confirm('Supprimer ?')"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Supprimer</button></form>
    <?php endif; ?></td></tr><?php endforeach; ?></tbody>
</table></div>
<?= $this->endSection() ?>
