<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($titre ?? 'Administration') ?></title>
    <link href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <h1 class="h3 mb-3"><?= esc($titre) ?> <span class="badge text-bg-secondary"><?= esc($role) ?></span></h1>
    <div class="row g-3 mb-4">
        <?php foreach (['utilisateurs' => 'Utilisateurs', 'employes' => 'Employés actifs', 'departements' => 'Départements', 'audits' => 'Lignes d\'audit'] as $k => $lib): ?>
            <div class="col-6 col-md-3"><div class="card text-center"><div class="card-body"><div class="display-6"><?= (int) ($stats[$k] ?? 0) ?></div><?= $lib ?></div></div></div>
        <?php endforeach; ?>
    </div>
    <?php
    $parSection = [];
    foreach ($liens as $l) { $parSection[$l['section']][] = $l; }
    ?>
    <div class="row g-3">
        <?php foreach ($parSection as $section => $items): ?>
            <div class="col-md-6 col-lg-4"><div class="card h-100"><div class="card-header fw-semibold"><?= esc($section) ?></div>
                <div class="card-body d-flex flex-wrap gap-2">
                    <?php foreach ($items as $i): ?><a class="btn btn-sm btn-outline-primary" href="<?= site_url($i['url']) ?>"><?= esc($i['label']) ?></a><?php endforeach; ?>
                </div></div></div>
        <?php endforeach; ?>
    </div>
    <p class="mt-4"><a href="<?= site_url('deconnexion') ?>">Déconnexion</a></p>
</div>
</body>
</html>
