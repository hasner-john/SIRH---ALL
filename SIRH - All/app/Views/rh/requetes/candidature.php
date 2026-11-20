<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<a href="<?= site_url('rh/requetes') ?>" class="small">&larr; Retour</a>
<h3 class="mt-2">Requêtes de <?= esc($cand['prenom'] . ' ' . $cand['nom']) ?></h3>
<p class="text-muted"><?= esc($cand['titre']) ?></p>
<table class="table bg-white align-middle">
  <thead><tr><th>Condition</th><th>Valeur actuelle</th><th>Proposition</th><th>Motif</th><th>Statut</th><th>Décision RH</th></tr></thead>
  <tbody>
  <?php foreach ($requetes as $r): ?>
    <tr>
      <td><?= esc($r['libelle']) ?></td>
      <td><?= esc($r['valeur_actuelle']) ?></td>
      <td><strong><?= esc($r['valeur_proposee']) ?></strong></td>
      <td><?= esc($r['commentaire_candidat'] ?? '') ?></td>
      <td><span class="badge text-bg-secondary"><?= esc($r['statut']) ?></span></td>
      <td>
        <?php if ($r['statut'] === 'SOUMISE'): ?>
          <form method="post" action="<?= site_url('rh/requetes/' . $r['id_requete'] . '/decision') ?>" class="d-flex gap-1">
            <?= csrf_field() ?>
            <input type="text" name="commentaire" class="form-control form-control-sm" placeholder="Commentaire">
            <button name="decision" value="accepter" class="btn btn-sm btn-success">Accepter</button>
            <button name="decision" value="refuser" class="btn btn-sm btn-danger">Refuser</button>
          </form>
        <?php else: ?>
          <span class="text-muted small">Traitée — transmise à la Direction si acceptée.</span>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?= $this->endSection() ?>
