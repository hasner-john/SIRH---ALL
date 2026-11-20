<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($titre ?? 'SIRH') ?></title>
    <link href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>" rel="stylesheet">
    <style>
        body { background: #f1f5f9; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark bg-dark">
    <div class="container">
        <span class="navbar-brand">SIRH — Administration</span>
        <a class="btn btn-outline-light btn-sm" href="<?= site_url('deconnexion') ?>">Déconnexion</a>
    </div>
</nav>
<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h1 class="h3 mb-1">Espace administrateur</h1><p class="text-muted mb-0">Rôle : <?= esc($role ?? '—') ?></p></div>
        <a class="btn btn-primary" href="<?= site_url('rh') ?>">Ouvrir l’espace RH</a>
        <a class="btn btn-outline-primary" href="<?= site_url('rh/demandes') ?>">Valider les demandes RH</a>
    </div>
    <div class="row g-3">
        <?php foreach ([['Utilisateurs', $stats['utilisateurs'], 'primary'], ['Employés actifs', $stats['employes'], 'success'], ['Départements', $stats['departements'], 'warning'], ['Événements audités', $stats['audits'], 'dark']] as [$label, $value, $color]): ?>
        <div class="col-sm-6 col-lg-3"><div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted"><?= esc($label) ?></div><div class="display-6 text-<?= esc($color) ?>"><?= esc($value) ?></div></div></div></div>
        <?php endforeach; ?>
    </div>
    <div class="card shadow-sm mt-4"><div class="card-body">
        <h2 class="h5">Maintenance</h2>
        <p class="text-muted mb-2">Réinitialisez les données depuis le terminal avec les commandes disponibles :</p>
        <code>php spark db:reset --force</code>
    </div></div>
</main>
<script src="<?= base_url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
