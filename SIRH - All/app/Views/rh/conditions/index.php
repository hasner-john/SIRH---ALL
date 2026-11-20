
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Conditions de contrat</title>
</head>
<body>
    <h1>Conditions de contrat</h1>

    <?php if (session()->getFlashdata('message')): ?>
        <p><?= esc(session()->getFlashdata('message')) ?></p>
    <?php endif; ?>

    <?php if (session()->getFlashdata('erreur')): ?>
        <p><?= esc(session()->getFlashdata('erreur')) ?></p>
    <?php endif; ?>

    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>Poste</th>
                <th>Département</th>
                <th>Statut</th>
                <th>Conditions actives</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($postes as $poste): ?>
                <tr>
                    <td><?= esc($poste['libelle']) ?></td>
                    <td><?= esc($poste['departement']) ?></td>
                    <td><?= esc($poste['statut']) ?></td>
                    <td><?= esc($poste['nombre_conditions']) ?></td>
                    <td>
                        <a href="<?= site_url('rh/conditions/' . $poste['id_poste']) ?>">
                            Gérer les conditions
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p><a href="<?= site_url('rh') ?>">Retour à l'accueil RH</a></p>
</body>
</html>
