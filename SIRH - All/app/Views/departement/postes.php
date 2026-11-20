<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title><?= esc($titre) ?></title>
<style>
body{font-family:system-ui,sans-serif;max-width:1050px;margin:2rem auto;padding:0 1rem}a,button{color:#fff;background:#2563eb;border:0;border-radius:5px;padding:.55rem .8rem;text-decoration:none;cursor:pointer}nav{display:flex;gap:.75rem;flex-wrap:wrap;margin:1rem 0}table{width:100%;border-collapse:collapse}th,td{border-bottom:1px solid #dbe2ea;padding:.65rem;text-align:left}select{padding:.5rem}.message{padding:.75rem;background:#dcfce7}
</style></head>
<body>
<p style="text-align:right"><a href="<?= site_url('deconnexion') ?>">Déconnexion</a></p>
<h1><?= esc($titre) ?></h1>
<?php if (session()->getFlashdata('success')): ?><p class="message"><?= esc(session()->getFlashdata('success')) ?></p><?php endif; ?>
<?php if (session()->getFlashdata('error')): ?><p class="message"><?= esc(session()->getFlashdata('error')) ?></p><?php endif; ?>
<nav><a href="<?= site_url('departement') ?>">Accueil</a><a href="<?= site_url('departement/postes/nouveau') ?>">Ajouter un poste</a><a href="<?= site_url('departement/demandes/nouveau') ?>">Nouvelle demande</a></nav>
<form method="get">
    <label for="departement">Département :</label>
    <select id="departement" name="departement">
        <option value="">Tous</option>
        <?php foreach ($departements as $departement): ?>
            <option value="<?= esc($departement['id_departement']) ?>" <?= $selectedDepartment === (int) $departement['id_departement'] ? 'selected' : '' ?>><?= esc($departement['nom']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Filtrer</button>
</form>
<table><tr><th>Libellé</th><th>Département</th><th>Statut</th><th>Création</th><th>Actions</th></tr>
<?php foreach ($postes as $poste): ?><tr><td><?= esc($poste['libelle']) ?></td><td><?= esc($poste['departement_nom']) ?></td><td><?= esc($poste['statut']) ?></td><td><?= esc($poste['date_creation']) ?></td><td><a href="<?= site_url('departement/postes/' . $poste['id_poste'] . '/conditions') ?>">Conditions</a><form method="post" style="display:inline;margin-left:.5rem" action="<?= site_url('departement/postes/' . $poste['id_poste'] . '/statut') ?>"><?= csrf_field() ?><select name="statut"><option value="ACTIF">ACTIF</option><option value="INACTIF">INACTIF</option><option value="FERME">FERME</option></select><button type="submit">Mettre à jour</button></form></td></tr><?php endforeach; ?>
</table>
</body></html>
