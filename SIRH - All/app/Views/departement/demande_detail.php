<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"><title><?= esc($titre) ?></title>
<style>body{font-family:system-ui,sans-serif;max-width:900px;margin:2rem auto;padding:0 1rem}a{color:#2563eb}dt{font-weight:700;margin-top:.8rem}dd{margin:.2rem 0}.message{padding:.75rem;background:#dcfce7}table{width:100%;border-collapse:collapse;margin-top:1rem}th,td{border-bottom:1px solid #dbe2ea;padding:.6rem;text-align:left}</style></head>
<body><h1><?= esc($titre) ?></h1>
<p style="text-align:right"><a href="<?= site_url('deconnexion') ?>">Déconnexion</a></p>
<?php if (session()->getFlashdata('success')): ?><p class="message"><?= esc(session()->getFlashdata('success')) ?></p><?php endif; ?>
<p><a href="<?= site_url('departement/demandes') ?>">Retour aux demandes</a></p>
<dl><dt>Poste</dt><dd><?= esc($demande['poste_libelle']) ?></dd><dt>Département</dt><dd><?= esc($demande['departement_nom']) ?></dd><dt>Effectif</dt><dd><?= esc($demande['effectif_demande']) ?></dd><dt>Motif</dt><dd><?= nl2br(esc($demande['motif'])) ?></dd><dt>Salaire par personne</dt><dd><?= esc($demande['salaire_par_personne']) ?></dd><dt>Date du besoin</dt><dd><?= esc($demande['date_besoin']) ?></dd><dt>Statut</dt><dd><?= esc($demande['statut']) ?></dd></dl>
<h2>Historique</h2><table><tr><th>Ancien statut</th><th>Nouveau statut</th><th>Date</th><th>Commentaire</th></tr><?php foreach ($historique as $ligne): ?><tr><td><?= esc($ligne['ancien_statut'] ?? '—') ?></td><td><?= esc($ligne['nouveau_statut']) ?></td><td><?= esc($ligne['date_action']) ?></td><td><?= esc($ligne['commentaire'] ?? '') ?></td></tr><?php endforeach; ?></table>
</body></html>
