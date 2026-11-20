<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"><title><?= esc($titre) ?></title>
<style>body{font-family:system-ui,sans-serif;max-width:1050px;margin:2rem auto;padding:0 1rem}a,button{color:#fff;background:#2563eb;border:0;border-radius:5px;padding:.55rem .8rem;text-decoration:none;cursor:pointer}.row{display:grid;grid-template-columns:2fr 1fr 2fr 1fr;gap:.5rem;margin:.5rem 0}input,select{padding:.5rem}.message{padding:.75rem;background:#dcfce7}.error{color:#b91c1c}@media(max-width:700px){.row{grid-template-columns:1fr}}</style></head>
<body><h1><?= esc($titre) ?></h1><p>Poste : <strong><?= esc($poste['libelle']) ?></strong> — <?= esc($poste['departement_nom']) ?></p>
<p style="text-align:right"><a href="<?= site_url('deconnexion') ?>">Déconnexion</a></p>
<?php if (session()->getFlashdata('success')): ?><p class="message"><?= esc(session()->getFlashdata('success')) ?></p><?php endif; ?>
<?php if (session()->getFlashdata('error')): ?><p class="error"><?= esc(session()->getFlashdata('error')) ?></p><?php endif; ?>
<p><a href="<?= site_url('departement/postes') ?>">Retour aux postes</a></p>
<?php if ($conditions !== []): ?>
<h2>Conditions enregistrées</h2>
<table>
<tr><th>Libellé</th><th>Type</th><th>Valeur</th><th>Ordre</th><th>Statut</th></tr>
<?php foreach ($conditions as $condition): ?>
<tr>
    <td><?= esc($condition['libelle']) ?></td>
    <td><?= esc($condition['type_valeur']) ?></td>
    <td><?= esc($condition['valeur_condition']) ?></td>
    <td><?= esc($condition['ordre_affichage']) ?></td>
    <td><?= esc($condition['statut']) ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<h2>Ajouter des conditions</h2>
<form method="post" action="<?= site_url('departement/postes/' . $poste['id_poste'] . '/conditions') ?>">
<?= csrf_field() ?>
<div id="conditions">
<div class="row"><input name="libelle[]" placeholder="Libellé" required><select name="type_valeur[]" required><?php foreach ($typesValeur as $type): ?><option value="<?= esc($type) ?>"><?= esc($type) ?></option><?php endforeach; ?></select><input name="valeur_condition[]" placeholder="Valeur" required><input type="number" min="1" name="ordre_affichage[]" value="<?= count($conditions) + 1 ?>" required></div>
</div><button type="button" onclick="addCondition()">Ajouter une ligne</button> <button type="submit">Enregistrer les conditions</button>
</form>
<script>
function addCondition() {
    const row = document.querySelector('.row').cloneNode(true);
    row.querySelectorAll('input').forEach((input, index) => { input.value = index === 3 ? document.querySelectorAll('.row').length + 1 : ''; });
    document.getElementById('conditions').appendChild(row);
}
</script></body></html>
