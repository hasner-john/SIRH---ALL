<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3"><?= esc($titre) ?> — <?= esc($i['matricule'] . ' ' . $i['prenom'] . ' ' . $i['nom']) ?></h1>
<div class="card mb-3"><div class="card-body">
  <p class="mb-1"><span class="badge text-bg-secondary"><?= esc($i['statut']) ?></span> <span class="badge text-bg-warning"><?= esc($i['gravite']) ?></span> <?= esc($i['categorie']) ?> — <?= esc($i['date_incident']) ?></p>
  <strong><?= esc($i['objet']) ?></strong><p><?= esc($i['description']) ?></p>
  <ol class="mb-0">
    <li>Incident signalé le <?= esc($i['date_creation']) ?></li>
    <?php if ($dem): ?><li>Demande d'explications du <?= esc($dem['date_demande']) ?> (limite <strong><?= esc($dem['date_limite']) ?></strong>) : <?= esc($dem['texte']) ?></li><?php endif; ?>
    <?php if ($rep): ?><li>Réponse du <?= esc($rep['date_reponse']) ?><?= $rep['en_retard'] ? ' <span class="badge text-bg-danger">en retard</span>' : '' ?> : <em><?= esc($rep['texte']) ?></em>
      <?php if ($rep['piece_jointe_nom']): ?> — <a href="<?= site_url('rh/discipline/reponses/' . (int) $rep['id_reponse'] . '/piece') ?>"><?= esc($rep['piece_jointe_nom']) ?></a><?php endif; ?></li>
    <?php elseif ($dem): ?><li>Pas de réponse<?= $dem['date_limite'] < date('Y-m-d') ? ' — <strong>délai dépassé</strong>' : ' — délai restant : ' . max(0, (int) ((strtotime($dem['date_limite']) - strtotime(date('Y-m-d'))) / 86400)) . ' jour(s)' ?></li><?php endif; ?>
    <?php if ($i['date_commission']): ?><li>Commission le <?= esc($i['date_commission']) ?> : <?= esc($i['commentaire_commission']) ?></li><?php endif; ?>
    <?php if ($sanction): ?><li>Sanction <strong><?= esc($sanction['type_sanction']) ?></strong> (<?= esc($sanction['statut']) ?>) du <?= esc($sanction['date_decision']) ?>, effet <?= esc($sanction['date_effet']) ?><?= $sanction['nb_jours'] ? ' — ' . (int) $sanction['nb_jours'] . ' jour(s)' : '' ?> : <?= esc($sanction['motif']) ?></li><?php endif; ?>
    <?php if ($i['decision_finale'] === 'CLASSE_SANS_SUITE'): ?><li>Classé sans suite</li><?php endif; ?>
  </ol></div></div>

<?php if ($i['statut'] === 'SIGNALE'): ?>
<form method="post" action="<?= site_url('rh/discipline/incidents/' . (int) $i['id_incident'] . '/explications') ?>" class="bg-white border rounded p-3 mb-3 row g-2"><?= csrf_field() ?>
  <h2 class="h6">Demander des explications</h2>
  <div class="col-md-3"><input class="form-control" type="date" name="date_limite" value="<?= date('Y-m-d', strtotime('+' . $delaiDefaut . ' days')) ?>" required></div>
  <div class="col-md-9"><input class="form-control" name="texte" value="Merci de fournir vos explications écrites sur les faits reprochés (<?= esc($i['objet']) ?>)."></div>
  <div class="col-12"><button class="btn btn-primary">Envoyer la demande</button></div></form>
<?php endif; ?>

<?php if ($i['statut'] === 'EXPLICATIONS'): ?>
<form method="post" enctype="multipart/form-data" action="<?= site_url('rh/discipline/incidents/' . (int) $i['id_incident'] . '/reponse') ?>" class="bg-white border rounded p-3 mb-3 row g-2"><?= csrf_field() ?>
  <h2 class="h6">Saisir une réponse papier au nom de l'employé</h2>
  <div class="col-md-3"><input class="form-control" type="date" name="date_reponse" value="<?= date('Y-m-d') ?>"></div>
  <div class="col-md-5"><input class="form-control" name="texte" placeholder="Texte de la réponse" required></div>
  <div class="col-md-4"><input class="form-control" type="file" name="piece" accept=".pdf,.jpg,.jpeg,.png"></div>
  <div class="col-12"><button class="btn btn-outline-primary">Enregistrer la réponse</button></div></form>
<?php endif; ?>

<?php if (in_array($i['statut'], ['REPONSE', 'EXPLICATIONS'], true) && $peutDecider): ?>
<form method="post" action="<?= site_url('rh/discipline/incidents/' . (int) $i['id_incident'] . '/commission') ?>" class="bg-white border rounded p-3 mb-3 row g-2"><?= csrf_field() ?>
  <h2 class="h6">Passer en commission (optionnel)</h2>
  <div class="col-md-3"><input class="form-control" type="date" name="date_commission" value="<?= date('Y-m-d') ?>"></div><div class="col-md-6"><input class="form-control" name="commentaire" placeholder="Commentaire"></div>
  <div class="col-md-3"><button class="btn btn-outline-secondary">Commission</button></div></form>
<?php endif; ?>

<?php if ($peutDecider): ?>
<form method="post" action="<?= site_url('rh/discipline/incidents/' . (int) $i['id_incident'] . '/decision') ?>" class="bg-white border rounded p-3 mb-3 row g-2"><?= csrf_field() ?>
  <h2 class="h6">Décision</h2>
  <?php if ($recidive): ?><div class="col-12"><div class="alert alert-warning mb-0">Alerte récidive : <?= count($recidive) ?> sanction(s) dans les <?= (int) $recidiveMois ?> derniers mois (<?= esc(implode(', ', array_map(fn ($r) => $r['type_sanction'] . ' le ' . $r['date_decision'], $recidive))) ?>).</div></div><?php endif; ?>
  <div class="col-md-3"><select class="form-select" name="decision" id="decision"><option value="SANCTION">Sanctionner</option><option value="CLASSE_SANS_SUITE">Classer sans suite</option></select></div>
  <div class="col-md-3 s"><select class="form-select" name="type_sanction" id="type"><?php foreach ($types as $t): ?><option><?= esc($t) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2 s"><input class="form-control" type="date" name="date_decision" value="<?= date('Y-m-d') ?>"></div>
  <div class="col-md-2 s"><input class="form-control" type="date" name="date_effet" value="<?= date('Y-m-d') ?>"></div>
  <div class="col-md-2 s"><input class="form-control" type="number" min="1" name="nb_jours" id="nb" placeholder="Jours (mise à pied)"></div>
  <div class="col-md-12 s"><input class="form-control" name="motif" placeholder="Motif de la sanction"></div>
  <div class="col-12"><button class="btn btn-danger" onclick="return confirm('Confirmer la décision ?')">Valider la décision</button></div></form>
<script>document.getElementById('decision').addEventListener('change',e=>document.querySelectorAll('.s').forEach(x=>x.style.display=e.target.value==='SANCTION'?'':'none'));</script>
<?php endif; ?>

<?php if ($sanction && in_array($sanction['statut'], ['NOTIFIEE', 'CONTESTEE'], true)): ?>
<form method="post" action="<?= site_url('rh/discipline/sanctions/' . (int) $sanction['id_sanction'] . '/statut') ?>" class="bg-white border rounded p-3"><?= csrf_field() ?>
  <h2 class="h6">Statut de la sanction</h2><?php if ($sanction['commentaire_contestation']): ?><p>Contestation : <em><?= esc($sanction['commentaire_contestation']) ?></em></p><?php endif; ?>
  <button class="btn btn-success btn-sm" name="statut" value="EXECUTOIRE">Rendre exécutoire</button> <button class="btn btn-outline-danger btn-sm" name="statut" value="ANNULEE">Annuler</button></form>
<?php endif; ?>
<?= $this->endSection() ?>
