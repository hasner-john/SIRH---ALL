<?= $this->extend('employe/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3"><?= esc($titre) ?></h1>
<?php if (! $incidents): ?><p class="text-muted">Aucun dossier.</p><?php endif; ?>
<?php foreach ($incidents as $i): ?><div class="card mb-3"><div class="card-body">
  <strong><?= esc($i['objet']) ?></strong> <span class="badge text-bg-secondary"><?= esc($i['statut']) ?></span> <small class="text-muted"><?= esc($i['date_incident']) ?></small>
  <p class="mb-1"><?= esc($i['description']) ?></p>
  <?php if ($i['demande']): ?><p class="mb-1"><em>Demande d'explications (avant le <?= esc($i['date_limite']) ?>) :</em> <?= esc($i['demande']) ?></p><?php endif; ?>
  <?php if ($i['reponse']): ?><p class="mb-1"><em>Votre réponse du <?= esc($i['date_reponse']) ?><?= $i['en_retard'] ? ' (en retard)' : '' ?> :</em> <?= esc($i['reponse']) ?></p>
  <?php elseif ($i['statut'] === 'EXPLICATIONS'): ?>
    <form method="post" enctype="multipart/form-data" action="<?= site_url('employe/discipline/' . (int) $i['id_incident'] . '/reponse') ?>" class="border rounded p-2"><?= csrf_field() ?>
      <textarea class="form-control mb-2" name="texte" placeholder="Vos explications" required></textarea>
      <input class="form-control mb-2" type="file" name="piece" accept=".pdf,.jpg,.jpeg,.png"><small class="text-muted">pdf, jpg ou png — 2 Mo maximum</small><br>
      <button class="btn btn-success btn-sm mt-1">Envoyer ma réponse</button></form>
  <?php endif; ?>
  <?php if ($i['id_sanction']): ?><div class="alert alert-warning mt-2 mb-0">Sanction : <strong><?= esc($i['type_sanction']) ?></strong> (<?= esc($i['statut_sanction']) ?>) à compter du <?= esc($i['date_effet']) ?><?= $i['nb_jours'] ? ' — ' . (int) $i['nb_jours'] . ' jour(s)' : '' ?><br><?= esc($i['motif_sanction']) ?>
    <?php if ($i['statut_sanction'] === 'NOTIFIEE'): ?>
    <form method="post" action="<?= site_url('employe/discipline/sanctions/' . (int) $i['id_sanction'] . '/contester') ?>" class="d-flex gap-2 mt-2"><?= csrf_field() ?><input class="form-control form-control-sm" name="commentaire" placeholder="Motif de contestation" required><button class="btn btn-sm btn-outline-danger">Contester</button></form>
    <?php endif; ?></div><?php endif; ?>
</div></div><?php endforeach; ?>
<?= $this->endSection() ?>
