<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= esc($titre) ?></title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 1100px; margin: 2rem auto; padding: 0 1rem; color: #172033; }
        nav, .actions { display: flex; gap: .75rem; flex-wrap: wrap; margin: 1rem 0; }
        a, button { color: #fff; background: #2563eb; border: 0; border-radius: 5px; padding: .55rem .8rem; text-decoration: none; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin: 1rem 0 2rem; } th, td { border-bottom: 1px solid #dbe2ea; padding: .65rem; text-align: left; }
        .muted { color: #64748b; } .message { padding: .75rem; background: #dcfce7; border-radius: 5px; }
        .filter { display: flex; gap: .5rem; align-items: center; } select, input { padding: .5rem; }
    </style>
</head>
<body>
    <h1><?= esc($titre) ?></h1>
    <p class="muted">Connecté en tant que <?= esc((string) session()->get('email')) ?></p>
    <?php if (session()->getFlashdata('success')): ?>
        <p class="message"><?= esc(session()->getFlashdata('success')) ?></p>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <p class="message"><?= esc(session()->getFlashdata('error')) ?></p>
    <?php endif; ?>

    <nav>
        <a href="<?= site_url('departement') ?>">Accueil</a>
        <a href="<?= site_url('departement/postes') ?>">Gérer les postes</a>
        <a href="<?= site_url('departement/postes/nouveau') ?>">Ajouter un poste</a>
        <a href="<?= site_url('departement/demandes') ?>">Demandes</a>
        <a href="<?= site_url('departement/demandes/nouveau') ?>">Nouvelle demande</a>
        <a href="<?= site_url('deconnexion') ?>">Déconnexion</a>
    </nav>

    <h2>Postes disponibles</h2>
    <?php if ($postes === []): ?>
        <p class="muted">Aucun poste disponible pour vos départements.</p>
    <?php else: ?>
        <table>
            <tr><th>Poste</th><th>Département</th><th>Statut</th><th>Action</th></tr>
            <?php foreach ($postes as $poste): ?>
                <tr>
                    <td><?= esc($poste['libelle']) ?></td>
                    <td><?= esc($poste['departement_nom']) ?></td>
                    <td><?= esc($poste['statut']) ?></td>
                    <td><a href="<?= site_url('departement/postes/' . $poste['id_poste'] . '/conditions') ?>">Conditions</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <h2>Demandes de recrutement</h2>
    <form class="filter" method="get">
        <label for="statut">Filtrer :</label>
        <select id="statut" name="statut">
            <option value="">Tous les statuts</option>
            <?php foreach (['BROUILLON', 'EN_ATTENTE', 'VALIDE', 'REFUSEE', 'ANNULEE'] as $status): ?>
                <option value="<?= esc($status) ?>" <?= $statusFiltre === $status ? 'selected' : '' ?>><?= esc($status) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Filtrer</button>
    </form>
    <table>
        <tr><th>Poste</th><th>Département</th><th>Effectif</th><th>Besoin</th><th>Statut</th><th></th></tr>
        <?php foreach ($demandes as $demande): ?>
            <tr>
                <td><?= esc($demande['poste_libelle']) ?></td>
                <td><?= esc($demande['departement_nom']) ?></td>
                <td><?= esc($demande['effectif_demande']) ?></td>
                <td><?= esc($demande['date_besoin']) ?></td>
                <td><?= esc($demande['statut']) ?></td>
                <td><a href="<?= site_url('departement/demandes/' . $demande['id_demande']) ?>">Détail</a></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
