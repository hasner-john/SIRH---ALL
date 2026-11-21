<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3"><?= esc($titre) ?></h1>
<form method="get" class="row g-2 mb-3">
  <div class="col-md-3"><select class="form-select" name="id_employe"><option value="">Employé</option><?php foreach ($employes as $e): ?><option value="<?= (int) $e['id_employe'] ?>" <?= ((int) ($filtre['id_employe'] ?? 0)) === (int) $e['id_employe'] ? 'selected' : '' ?>><?= esc($e['matricule'] . ' ' . $e['nom']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><input class="form-control" type="date" name="du" value="<?= esc($filtre['du'] ?? '') ?>"></div><div class="col-md-2"><input class="form-control" type="date" name="au" value="<?= esc($filtre['au'] ?? '') ?>"></div>
  <div class="col-md-2"><select class="form-select" name="piece"><option value="">Pièce jointe</option><option value="1" <?= ($filtre['piece'] ?? '') === '1' ? 'selected' : '' ?>>Avec</option><option value="0" <?= ($filtre['piece'] ?? '') === '0' ? 'selected' : '' ?>>Sans</option></select></div>
  <div class="col-md-2"><select class="form-select" name="en_retard"><option value="">Délai</option><option value="1" <?= ($filtre['en_retard'] ?? '') === '1' ? 'selected' : '' ?>>En retard</option></select></div>
  <div class="col-md-1"><button class="btn btn-outline-primary">OK</button></div>
</form>
<table class="table table-sm bg-white"><thead><tr><th>Date</th><th>Employé</th><th>Incident</th><th>Réponse</th><th>Retard</th><th>Pièce</th></tr></thead><tbody>
<?php foreach ($reponses as $r): ?><tr><td><?= esc($r['date_reponse']) ?></td><td><?= esc($r['matricule'] . ' ' . $r['nom']) ?></td><td><a href="<?= site_url('rh/discipline/incidents/' . (int) $r['id_incident']) ?>"><?= esc($r['objet']) ?></a></td><td><?= esc($r['texte']) ?></td>
  <td><?= $r['en_retard'] ? '<span class="badge text-bg-danger">oui</span>' : 'non' ?></td><td><?php if ($r['piece_jointe_nom']): ?><a href="<?= site_url('rh/discipline/reponses/' . (int) $r['id_reponse'] . '/piece') ?>"><?= esc($r['piece_jointe_nom']) ?></a><?php endif; ?></td></tr><?php endforeach; ?></tbody></table>
<?= $this->endSection() ?>
