<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3"><?= esc($titre) ?> — <?= esc($emp['matricule'] . ' ' . $emp['prenom'] . ' ' . $emp['nom']) ?></h1>
<form method="get" class="row g-2 mb-3"><div class="col-auto">Situation au :</div><div class="col-auto"><input class="form-control" type="date" name="date" value="<?= esc($date) ?>"></div><div class="col-auto"><button class="btn btn-primary">Voir</button></div></form>
<div class="card mb-4"><div class="card-body">
  <h2 class="h5">Situation au <?= esc(date('d/m/Y', strtotime($date))) ?></h2>
  <ul class="mb-0">
    <li>Poste : <?= esc($contrat['poste'] ?? '—') ?></li><li>Fonction : <?= esc($aff['fonction'] ?? '—') ?></li><li>Département : <?= esc($aff['departement'] ?? '—') ?></li>
    <li>Salaire : <?= $sal ? number_format((float) $sal['nouveau_salaire'], 0, ',', ' ') . ' Ar' : '—' ?></li>
    <li>Contrat : <?= $contrat ? esc($contrat['reference'] . ' (' . $contrat['type_contrat'] . ', ' . $contrat['statut'] . ')') : '—' ?></li>
  </ul></div></div>
<h2 class="h5">Frise des mouvements</h2>
<ul class="list-group"><?php foreach ($mouvements as $m): ?><li class="list-group-item"><strong><?= esc($m['date_effet']) ?></strong> — <?= esc($m['type_mouvement']) ?> (<?= esc($m['statut']) ?>) : <?= esc(($m['ancien_poste'] ?: '—') . ' → ' . ($m['nouveau_poste'] ?: '—')) ?> — <?= esc($m['motif']) ?></li><?php endforeach; ?></ul>
<?= $this->endSection() ?>
