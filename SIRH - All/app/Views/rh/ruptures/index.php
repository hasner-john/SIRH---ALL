<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3"><?= esc($titre) ?></h1>
<form method="post" action="<?= site_url('rh/ruptures') ?>" class="row g-2 mb-4 bg-white border rounded p-3"><?= csrf_field() ?>
  <div class="col-md-3"><select class="form-select" name="id_employe" required><option value="">Employé…</option><?php foreach ($employes as $e): ?><option value="<?= (int) $e['id_employe'] ?>"><?= esc($e['matricule'] . ' — ' . $e['prenom'] . ' ' . $e['nom']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><select class="form-select" name="motif"><?php foreach ($motifs as $m): ?><option><?= $m ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><input class="form-control" type="date" name="date_notification" value="<?= date('Y-m-d') ?>" required></div>
  <div class="col-md-2"><input class="form-control" type="date" name="date_sortie" title="Laisser vide : notification + préavis (ou fin du CDD)"></div>
  <div class="col-md-2"><input class="form-control" type="number" min="0" name="duree_preavis_jours" placeholder="Préavis (j)"></div>
  <div class="col-md-10"><input class="form-control" name="motif_detail" placeholder="Détails"></div>
  <div class="col-md-2"><button class="btn btn-warning">Créer la rupture</button></div>
</form>
<table class="table table-sm bg-white"><thead><tr><th>Employé</th><th>Motif</th><th>Notification</th><th>Sortie</th><th>Statut</th><th>STC</th><th></th></tr></thead><tbody>
<?php foreach ($ruptures as $r): ?><tr><td><?= esc($r['matricule'] . ' ' . $r['nom']) ?></td><td><?= esc($r['motif']) ?></td><td><?= esc($r['date_notification']) ?></td><td><?= esc($r['date_sortie']) ?></td><td><?= esc($r['statut']) ?></td>
  <td><?= $r['montant_net'] !== null ? number_format((float) $r['montant_net'], 0, ',', ' ') . ' Ar (' . esc($r['statut_stc']) . ')' : '—' ?></td><td><a href="<?= site_url('rh/ruptures/' . (int) $r['id_rupture']) ?>">Détail</a></td></tr><?php endforeach; ?></tbody></table>
<?= $this->endSection() ?>
