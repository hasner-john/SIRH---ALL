<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3"><?= esc($titre) ?></h1>
<form method="post" action="<?= site_url('rh/parametres') ?>" class="row g-2 mb-3 bg-white border rounded p-2 align-items-center">
  <?= csrf_field() ?><input type="hidden" name="code" value="SEUIL_ECART_FORMATION"><input type="hidden" name="actif" value="1">
  <div class="col-auto">Seuil d'écart déclenchant une formation :</div>
  <div class="col-auto"><input class="form-control form-control-sm" type="number" step="any" name="valeur" value="<?= esc($seuil) ?>" style="width:90px"></div>
  <div class="col-auto"><button class="btn btn-sm btn-primary">Enregistrer</button></div>
  <div class="col-auto"><button class="btn btn-sm btn-outline-secondary" formaction="<?= site_url('rh/formations/propositions/recalculer') ?>" formnovalidate>Recalculer les propositions</button></div>
</form>
<form method="get" class="row g-2 mb-3">
  <div class="col-md-3"><select class="form-select" name="id_employe"><option value="">Tous les employés</option><?php foreach ($employes as $e): ?><option value="<?= (int) $e['id_employe'] ?>" <?= ((int) ($filtre['id_employe'] ?? 0)) === (int) $e['id_employe'] ? 'selected' : '' ?>><?= esc($e['matricule'] . ' ' . $e['nom']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><input class="form-control" name="periode" placeholder="Période" value="<?= esc($filtre['periode'] ?? '') ?>"></div>
  <div class="col-md-2"><select class="form-select" name="statut"><option value="">Statut</option><?php foreach (['BROUILLON', 'VALIDE', 'ARCHIVE'] as $s): ?><option <?= ($filtre['statut'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><select class="form-select" name="type_evaluation"><option value="">Type</option><?php foreach (['INITIALE', 'PERIODIQUE', 'POST_FORMATION'] as $s): ?><option <?= ($filtre['type_evaluation'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></div>
  <div class="col-md-auto"><button class="btn btn-outline-primary">Filtrer</button></div>
  <div class="col-md-3"><select class="form-select" onchange="if(this.value)location='<?= site_url('rh/performance/nouvelle') ?>/'+this.value"><option value="">+ Nouvelle évaluation pour…</option><?php foreach ($employes as $e): ?><option value="<?= (int) $e['id_employe'] ?>"><?= esc($e['matricule'] . ' ' . $e['nom']) ?></option><?php endforeach; ?></select></div>
</form>
<div class="table-responsive"><table class="table table-sm bg-white">
  <thead><tr><th>#</th><th>Date</th><th>Employé</th><th>Période</th><th>Type</th><th>Note</th><th>Statut</th><th></th></tr></thead>
  <tbody><?php foreach ($evaluations as $v): ?><tr>
    <td><?= (int) $v['id_evaluation_performance'] ?></td><td><?= esc($v['date_evaluation']) ?></td><td><?= esc($v['matricule'] . ' ' . $v['nom']) ?></td>
    <td><?= esc($v['periode']) ?></td><td><?= esc($v['type_evaluation']) ?></td><td><?= esc($v['note_globale']) ?></td><td><?= esc($v['statut']) ?></td>
    <td><a href="<?= site_url('rh/performance/' . (int) $v['id_evaluation_performance']) ?>">Détail</a></td></tr><?php endforeach; ?></tbody>
</table></div>
<?= $this->endSection() ?>
