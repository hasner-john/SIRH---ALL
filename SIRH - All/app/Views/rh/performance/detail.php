<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3"><?= esc($titre) ?> — <?= esc($ev['matricule'] . ' ' . $ev['prenom'] . ' ' . $ev['nom']) ?></h1>
<p>Période <strong><?= esc($ev['periode']) ?></strong> — <?= esc($ev['type_evaluation']) ?> — note <?= esc($ev['note_globale'] ?? '—') ?>/20 — <span class="badge text-bg-secondary"><?= esc($ev['statut']) ?></span><?= $prec ? ' — précédente : ' . esc($prec['periode']) : '' ?></p>
<p><?= esc($ev['commentaire']) ?></p>
<table class="table table-sm bg-white"><thead><tr><th>Compétence</th><th>Attendu</th><th>Acquis</th><th>Écart</th><th>vs précédente</th></tr></thead><tbody>
<?php foreach ($lignes as $l): $depasse = $l['ecart'] !== null && (float) $l['ecart'] > (float) $seuil;
  $cmp = $l['precedent'] === null ? '' : ($l['niveau_acquis'] > $l['precedent'] ? '↑' : ($l['niveau_acquis'] < $l['precedent'] ? '↓' : '=')); ?>
  <tr><td><?= esc($l['code'] . ' — ' . $l['libelle']) ?></td><td><?= esc($l['niveau_attendu'] ?? '—') ?></td><td><?= (int) $l['niveau_acquis'] ?></td>
    <td><?= $l['ecart'] === null ? '—' : ($depasse ? '<span class="badge text-bg-danger">' . (int) $l['ecart'] . '</span>' : (int) $l['ecart']) ?></td><td><?= $cmp ?></td></tr>
<?php endforeach; ?></tbody></table>
<?php if ($props): ?><h2 class="h5">Propositions de formation générées</h2><ul>
  <?php foreach ($props as $p): ?><li><?= esc($p['libelle']) ?> — écart <?= (int) $p['ecart'] ?> (seuil <?= esc($p['seuil_applique'] + 0) ?>) — <?= esc($p['statut']) ?></li><?php endforeach; ?></ul><?php endif; ?>
<?php if ($ev['statut'] === 'BROUILLON'): ?>
<form method="post" action="<?= site_url('rh/performance/' . (int) $ev['id_evaluation_performance'] . '/valider') ?>"><?= csrf_field() ?><button class="btn btn-success" onclick="return confirm('Une évaluation validée n\'est plus modifiable. Valider ?')">Valider l'évaluation</button></form>
<?php else: ?><p class="text-muted">Évaluation validée : non modifiable (une correction = nouvelle évaluation).</p><?php endif; ?>
<?= $this->endSection() ?>
