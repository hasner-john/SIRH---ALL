
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offres d'emploi - Espace RH</title>
    <link
        href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>"
        rel="stylesheet"
    >
</head>
<body class="bg-light">
<a class="btn btn-outline-danger position-fixed top-0 end-0 m-3" href="<?= site_url('deconnexion') ?>">Déconnexion</a>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2>Gestion des offres d'emploi</h2>
            <p class="text-muted mb-0">Consultez et gérez les offres de recrutement.</p>
        </div>

        <a href="<?= site_url('rh/offres/demandes') ?>" class="btn btn-primary">
            Créer une offre
        </a>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success">
            <?= esc(session()->getFlashdata('success')) ?>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger">
            <?= esc(session()->getFlashdata('error')) ?>
        </div>
    <?php endif; ?>

    <?php
    $couleurs = [
        'BROUILLON' => 'secondary',
        'EN_ATTENTE' => 'warning',
        'VALIDE' => 'info',
        'PUBLIEE' => 'success',
        'FERMEE' => 'dark',
        'REFUSEE' => 'danger'
    ];
    ?>

    <?php if (empty($offres)): ?>
        <div class="alert alert-info">
            Aucune offre d'emploi n'a encore été créée.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped bg-white align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Titre</th>
                        <th>Statut</th>
                        <th>Date de création</th>
                        <th>Date de publication</th>
                        <th>Date limite</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($offres as $offre): ?>
                        <tr>
                            <td><?= esc($offre['id_offre']) ?></td>
                            <td><?= esc($offre['titre']) ?></td>
                            <td>
                                <span class="badge bg-<?= $couleurs[$offre['statut']] ?? 'secondary' ?>">
                                    <?= esc($offre['statut']) ?>
                                </span>
                            </td>
                            <td><?= esc($offre['date_creation']) ?></td>
                            <td><?= esc($offre['date_publication'] ?? 'Non publiée') ?></td>
                            <td><?= esc($offre['date_limite'] ?? 'Aucune') ?></td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <a
                                        href="<?= site_url('rh/offres/' . $offre['id_offre']) ?>"
                                        class="btn btn-outline-primary btn-sm"
                                    >
                                        Détails
                                    </a>

                                    <?php if ($offre['statut'] === 'BROUILLON'): ?>
                                        <form
                                            action="<?= site_url('rh/offres/soumettre/' . $offre['id_offre']) ?>"
                                            method="post"
                                            onsubmit="return confirm('Soumettre cette offre pour validation ?');"
                                        >
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-warning btn-sm">
                                                Soumettre
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($offre['statut'] === 'VALIDE'): ?>
                                        <form
                                            action="<?= site_url('rh/offres/publier/' . $offre['id_offre']) ?>"
                                            method="post"
                                            onsubmit="return confirm('Publier cette offre ?');"
                                        >
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-success btn-sm">
                                                Publier
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($offre['statut'] === 'PUBLIEE'): ?>
                                        <form
                                            action="<?= site_url('rh/offres/fermer/' . $offre['id_offre']) ?>"
                                            method="post"
                                            onsubmit="return confirm('Fermer cette offre ?');"
                                        >
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                Fermer
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <a href="<?= site_url('rh') ?>" class="btn btn-secondary">
        Retour à l'espace RH
    </a>
</div>

</body>
</html>
