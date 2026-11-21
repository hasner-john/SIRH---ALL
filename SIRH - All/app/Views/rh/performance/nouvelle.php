<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3"><?= esc($titre) ?> — <?= esc($emp['matricule'] . ' ' . $emp['prenom'] . ' ' . $emp['nom']) ?></h1>
<form method="post" action="<?= site_url('rh/performance/enregistrer') ?>" class="bg-white border rounded p-3">
  <?= csrf_field() ?><input type="hidden" name="id_employe" value="<?= (int) $emp['id_employe'] ?>">
  <div class="row g-2 mb-3">
    <div class="col-md-3"><input class="form-control" name="periode" placeholder="Période (S1-2027)" required></div>
    <div class="col-md-3"><select class="form-select" name="type_evaluation"><option>PERIODIQUE</option><option>INITIALE</option></select></div>
    <div class="col-md-2"><input class="form-control" type="number" step="0.01" min="0" max="20" name="note_globale" placeholder="Note /20"></div>
    <div class="col-md-12"><textarea class="form-control" name="commentaire" placeholder="Commentaire"></textarea></div>
  </div>
  <table class="table table-sm"><thead><tr><th>Compétence</th><th>Attendu</th><th>Dernier niveau connu</th><th>Niveau acquis (1-5)</th></tr></thead><tbody>
  <?php foreach ($competences as $c): ?><tr><td><?= esc($c['code'] . ' — ' . $c['libelle']) ?></td><td><?= (int) $c['niveau_attendu'] ?></td><td><?= esc($c['dernier'] ?? '—') ?></td>
    <td><input class="form-control form-control-sm" style="width:80px" type="number" min="1" max="5" name="niveau[<?= (int) $c['id_competence'] ?>]"></td></tr><?php endforeach; ?>
  </tbody></table>
  <div class="row g-2 mb-3"><div class="col-md-4"><select class="form-select" name="hors_competence"><option value="">Compétence hors poste…</option><?php foreach ($hors as $h): ?><option value="<?= (int) $h['id_competence'] ?>"><?= esc($h['code']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><input class="form-control" type="number" min="1" max="5" name="hors_niveau" placeholder="Niveau"></div></div>
  <?php if ($objectifs): ?><h2 class="h6">Objectifs</h2><?php foreach ($objectifs as $o): ?>
    <div class="row g-2 mb-1"><div class="col-md-4"><?= esc($o['titre']) ?></div>
      <div class="col-md-3"><select class="form-select form-select-sm" name="objectif[<?= (int) $o['id_objectif'] ?>][statut]"><option value="">(inchangé)</option><?php foreach (['EN_COURS', 'ATTEINT', 'NON_ATTEINT', 'ANNULE'] as $s): ?><option><?= $s ?></option><?php endforeach; ?></select></div>
      <div class="col-md-5"><input class="form-control form-control-sm" name="objectif[<?= (int) $o['id_objectif'] ?>][resultat]" placeholder="Résultat"></div></div>
  <?php endforeach; endif; ?>
  <button class="btn btn-primary mt-3">Enregistrer (brouillon)</button>
</form>
<?= $this->endSection() ?>
