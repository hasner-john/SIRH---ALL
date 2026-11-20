<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"><title><?= esc($titre) ?></title>
<style>body{font-family:system-ui,sans-serif;max-width:700px;margin:2rem auto;padding:0 1rem}label{display:block;margin-top:1rem}input,select,textarea{width:100%;box-sizing:border-box;padding:.55rem}button,a{display:inline-block;margin-top:1rem;color:#fff;background:#2563eb;border:0;border-radius:5px;padding:.6rem .9rem;text-decoration:none}.error{color:#b91c1c}</style></head>
<body><h1><?= esc($titre) ?></h1>
<p style="text-align:right"><a href="<?= site_url('deconnexion') ?>">Déconnexion</a></p>
<?php if (session()->getFlashdata('error')): ?><p class="error"><?= esc(session()->getFlashdata('error')) ?></p><?php endif; ?>
<?php foreach ((array) session()->getFlashdata('errors') as $error): ?><p class="error"><?= esc($error) ?></p><?php endforeach; ?>
<form method="post" action="<?= site_url('departement/postes') ?>">
    <?= csrf_field() ?>
    <label for="id_departement">Département</label>
    <select id="id_departement" name="id_departement" required><option value="">Choisir</option>
        <?php foreach ($departements as $departement): ?><option value="<?= esc($departement['id_departement']) ?>" <?= old('id_departement') == $departement['id_departement'] ? 'selected' : '' ?>><?= esc($departement['nom']) ?></option><?php endforeach; ?>
    </select>
    <label for="libelle">Libellé du poste</label><input id="libelle" name="libelle" value="<?= esc(old('libelle')) ?>" required maxlength="150">
    <label for="description">Description</label><textarea id="description" name="description" maxlength="5000"><?= esc(old('description')) ?></textarea>
    <button type="submit">Créer le poste</button> <a href="<?= site_url('departement/postes') ?>">Annuler</a>
</form></body></html>
