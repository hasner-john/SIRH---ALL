<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3"><?= esc($titre) ?></h1>
<div class="mb-3"><a class="btn btn-primary btn-sm" href="<?= site_url('rh/carriere/mouvement') ?>">Nouveau mouvement</a> <a class="btn btn-outline-primary btn-sm" href="<?= site_url('rh/carriere/propositions') ?>">Propositions de carrière</a></div>
<div class="table-responsive"><table class="table table-sm bg-white"><thead><tr><th>Effet</th><th>Employé</th><th>Type</th><th>Avant → Après (poste)</th><th>Salaire</th><th>Motif</th><th>Statut</th><th></th></tr></thead><tbody>
<?php foreach ($mouvements as $m): ?><tr><td><?= esc($m['date_effet']) ?></td><td><a href="<?= site_url('rh/employes/' . (int) $m['id_employe'] . '/parcours') ?>"><?= esc($m['matricule'] . ' ' . $m['nom']) ?></a></td><td><?= esc($m['type_mouvement']) ?></td>
  <td><?= esc(($m['ancien_poste'] ?: '—') . ' → ' . ($m['nouveau_poste'] ?: '—')) ?></td>
  <td><?= $m['ancien_salaire'] !== null ? number_format((float) $m['ancien_salaire'], 0, ',', ' ') : '—' ?> → <?= $m['nouveau_salaire'] !== null ? number_format((float) $m['nouveau_salaire'], 0, ',', ' ') : '—' ?></td>
  <td><?= esc($m['motif']) ?></td><td><?= esc($m['statut']) ?></td>
  <td><?php if ($m['statut'] === 'PLANIFIE'): ?><form method="post" action="<?= site_url('rh/carriere/' . (int) $m['id_mouvement'] . '/annuler') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Annuler</button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div>
<?= $this->endSection() ?>
