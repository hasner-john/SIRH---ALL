
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Conditions — <?= esc($poste['libelle']) ?></title>
</head>
<body>
    <h1>Conditions du poste : <?= esc($poste['libelle']) ?></h1>
    <p>Département : <?= esc($poste['departement']) ?></p>

    <?php if (session()->getFlashdata('message')): ?>
        <p><?= esc(session()->getFlashdata('message')) ?></p>
    <?php endif; ?>

    <?php if (session()->getFlashdata('erreur')): ?>
        <p><?= esc(session()->getFlashdata('erreur')) ?></p>
    <?php endif; ?>

    <p>
        <a href="<?= site_url('rh/conditions/' . $poste['id_poste'] . '/create') ?>">
            Ajouter une condition
        </a>
    </p>

    <form method="post"
          action="<?= site_url('rh/conditions/' . $poste['id_poste'] . '/confirm') ?>">
        <?= csrf_field() ?>
        <button type="submit"
                onclick="return confirm('Confirmer les conditions en attente de ce poste ?')">
            Confirmer les conditions
        </button>
    </form>

    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>Ordre</th>
                <th>Libellé</th>
                <th>Type</th>
                <th>Valeur</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($conditions as $condition): ?>
                <tr>
                    <td><?= esc($condition['ordre_affichage']) ?></td>
                    <td><?= esc($condition['libelle']) ?></td>
                    <td><?= esc($condition['type_valeur']) ?></td>
                    <td><?= esc($condition['valeur_condition']) ?></td>
                    <td><?= esc($condition['statut']) ?></td>
                    <td>
                        <?php if ($condition['statut'] !== 'INACTIF'): ?>
                            <a href="<?= site_url(
                                'rh/conditions/edit/' . $condition['id_condition']
                            ) ?>">Modifier</a>

                            <form method="post"
                                  action="<?= site_url(
                                      'rh/conditions/' . $condition['id_condition'] . '/delete'
                                  ) ?>"
                                  style="display:inline">
                                <?= csrf_field() ?>
                                <button type="submit"
                                        onclick="return confirm('Désactiver cette condition ?')">
                                    Supprimer
                                </button>
                            </form>
                        <?php else: ?>
                            Condition inactive
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p><a href="<?= site_url('rh/conditions') ?>">Retour aux postes</a></p>
</body>
</html>
