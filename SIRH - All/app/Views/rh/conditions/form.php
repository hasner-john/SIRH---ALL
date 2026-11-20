<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>
        <?= $condition ? 'Modifier' : 'Ajouter' ?> une condition
    </title>
</head>

<body>
    <h1>
        <?= $condition ? 'Modifier une condition' : 'Ajouter une condition' ?>
    </h1>

    <p>Poste : <?= esc($poste['libelle']) ?></p>

    <?php if (session()->getFlashdata('erreur')): ?>
        <p><?= esc(session()->getFlashdata('erreur')) ?></p>
    <?php endif; ?>

    <form method="post" action="<?= esc($action) ?>">
        <?= csrf_field() ?>

        <div>
            <label for="libelle">Libellé :</label>
            <input
                type="text"
                id="libelle"
                name="libelle"
                maxlength="180"
                required
                value="<?= esc(old('libelle', $condition['libelle'] ?? '')) ?>">
        </div>

        <div>
            <label for="type_valeur">Type de valeur :</label>
            <?php $typeActuel = old('type_valeur', $condition['type_valeur'] ?? 'TEXTE'); ?>
            <select id="type_valeur" name="type_valeur" required>
                <?php foreach (['TEXTE', 'NOMBRE', 'MONTANT', 'DATE', 'BOOLEEN'] as $type): ?>
                    <option value="<?= esc($type) ?>"
                        <?= $typeActuel === $type ? 'selected' : '' ?>>
                        <?= esc($type) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="valeur_condition">Valeur :</label>
            <textarea
                id="valeur_condition"
                name="valeur_condition"
                required><?= esc(old('valeur_condition', $condition['valeur_condition'] ?? '')) ?></textarea>
        </div>

        <div>
            <label for="ordre_affichage">Ordre d'affichage :</label>
            <input
                type="number"
                id="ordre_affichage"
                name="ordre_affichage"
                min="1"
                required
                value="<?= esc(old('ordre_affichage', $condition['ordre_affichage'] ?? '1')) ?>">
        </div>

        <button type="submit">Enregistrer</button>
    </form>

    <p>
        <a href="<?= site_url('rh/conditions/' . $poste['id_poste']) ?>">
            Annuler et revenir aux conditions
        </a>
    </p>
</body>

</html>