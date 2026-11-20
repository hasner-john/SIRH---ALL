<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"><title><?= esc($titre) ?></title>
<style>body{font-family:system-ui,sans-serif;max-width:700px;margin:2rem auto;padding:0 1rem}label{display:block;margin-top:1rem}input,select,textarea{width:100%;box-sizing:border-box;padding:.55rem}button,a{display:inline-block;margin-top:1rem;color:#fff;background:#2563eb;border:0;border-radius:5px;padding:.6rem .9rem;text-decoration:none}.error{color:#b91c1c}</style></head>
<body><h1><?= esc($titre) ?></h1>
<p style="text-align:right"><a href="<?= site_url('deconnexion') ?>">Déconnexion</a></p>
<?php if (session()->getFlashdata('error')): ?><p class="error"><?= esc(session()->getFlashdata('error')) ?></p><?php endif; ?>
<?php foreach ((array) session()->getFlashdata('errors') as $error): ?><p class="error"><?= esc($error) ?></p><?php endforeach; ?>
<form method="post" action="<?= site_url('departement/demandes') ?>">
<?= csrf_field() ?>
<label for="id_departement">Département</label><select id="id_departement" name="id_departement" required><option value="">Choisir</option><?php foreach ($departements as $departement): ?><option value="<?= esc($departement['id_departement']) ?>" <?= old('id_departement', $selectedDepartment) == $departement['id_departement'] ? 'selected' : '' ?>><?= esc($departement['nom']) ?></option><?php endforeach; ?></select>
<label for="id_poste">Poste</label><select id="id_poste" name="id_poste" required><option value="">Choisir un département</option><?php foreach ($postes as $poste): ?><option value="<?= esc($poste['id_poste']) ?>" data-departement="<?= esc($poste['id_departement']) ?>" <?= old('id_poste', $selectedPost) == $poste['id_poste'] ? 'selected' : '' ?>><?= esc($poste['libelle']) ?></option><?php endforeach; ?></select>
<p><a href="<?= site_url('departement/postes/nouveau') ?>">Créer un nouveau poste</a></p>
<label for="effectif_demande">Effectif demandé</label><input id="effectif_demande" type="number" min="1" name="effectif_demande" value="<?= esc(old('effectif_demande')) ?>" required>
<label for="motif">Motif</label><textarea id="motif" name="motif" required maxlength="5000"><?= esc(old('motif')) ?></textarea>
<label for="salaire_par_personne">Salaire par personne</label><input id="salaire_par_personne" type="number" min="0" step="0.01" name="salaire_par_personne" value="<?= esc(old('salaire_par_personne')) ?>" required>
<label for="date_besoin">Date du besoin</label><input id="date_besoin" type="date" name="date_besoin" value="<?= esc(old('date_besoin')) ?>" required>
<button type="submit">Enregistrer en brouillon</button> <a href="<?= site_url('departement/demandes') ?>">Annuler</a>
</form>
<script>
const department = document.getElementById('id_departement');
const post = document.getElementById('id_poste');
function filterPosts() {
    const value = department.value;
    Array.from(post.options).forEach(option => {
        option.hidden = option.value !== '' && option.dataset.departement !== value;
    });
    if (post.selectedOptions[0] && post.selectedOptions[0].hidden) post.value = '';
}
department.addEventListener('change', filterPosts);
filterPosts();
</script></body></html>
