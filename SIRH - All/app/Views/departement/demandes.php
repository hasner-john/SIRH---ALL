<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"><title><?= esc($titre) ?></title>
<style>body{font-family:system-ui,sans-serif;max-width:1100px;margin:2rem auto;padding:0 1rem}a,button{color:#fff;background:#2563eb;border:0;border-radius:5px;padding:.55rem .8rem;text-decoration:none;cursor:pointer}nav{display:flex;gap:.75rem;margin:1rem 0;flex-wrap:wrap}table{width:100%;border-collapse:collapse}th,td{border-bottom:1px solid #dbe2ea;padding:.65rem;text-align:left}select{padding:.5rem}.message{padding:.75rem;background:#dcfce7}</style></head>
<body><h1><?= esc($titre) ?></h1>
<p style="text-align:right"><a href="<?= site_url('deconnexion') ?>">Déconnexion</a></p>
<?php if (session()->getFlashdata('success')): ?><p class="message"><?= esc(session()->getFlashdata('success')) ?></p><?php endif; ?>
<nav><a href="<?= site_url('departement') ?>">Accueil</a><a href="<?= site_url('departement/demandes/nouveau') ?>">Nouvelle demande</a></nav>
<form method="get"><label for="statut">Statut :</label><select id="statut" name="statut"><option value="">Tous</option><?php foreach (['BROUILLON','EN_ATTENTE','VALIDE','REFUSEE','ANNULEE'] as $status): ?><option value="<?= esc($status) ?>" <?= $statusFiltre === $status ? 'selected' : '' ?>><?= esc($status) ?></option><?php endforeach; ?></select> <button type="submit">Filtrer</button></form>
<table><tr><th>Poste</th><th>Département</th><th>Effectif</th><th>Salaire</th><th>Date du besoin</th><th>Statut</th><th></th></tr>
<?php foreach ($demandes as $demande): ?><tr><td><?= esc($demande['poste_libelle']) ?></td><td><?= esc($demande['departement_nom']) ?></td><td><?= esc($demande['effectif_demande']) ?></td><td><?= esc($demande['salaire_par_personne']) ?></td><td><?= esc($demande['date_besoin']) ?></td><td><?= esc($demande['statut']) ?></td><td><a href="<?= site_url('departement/demandes/' . $demande['id_demande']) ?>">Détail</a></td></tr><?php endforeach; ?>
</table></body></html>
