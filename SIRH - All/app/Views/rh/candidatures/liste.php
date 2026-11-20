
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Candidatures de l'offre</title>
    <link href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>"
          rel="stylesheet">
</head>
<body class="bg-light">
<a class="btn btn-outline-danger position-fixed top-0 end-0 m-3" href="<?= site_url('deconnexion') ?>">Déconnexion</a>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3">Candidatures</h1>
            <p class="text-muted mb-0">
                Offre n° <?= esc($idOffre) ?>
            </p>
        </div>
        <a href="<?= site_url('rh/offres/' . $idOffre) ?>"
           class="btn btn-outline-secondary">
            Retour à l'offre
        </a>
    </div>

    <?php if (session()->getFlashdata('succes')): ?>
        <div class="alert alert-success">
            <?= esc(session()->getFlashdata('succes')) ?>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('erreur')): ?>
        <div class="alert alert-danger">
            <?= esc(session()->getFlashdata('erreur')) ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($candidatures)): ?>
                <p class="text-muted mb-0">
                    Aucune candidature pour cette offre.
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Candidat</th>
                            <th>Date de candidature</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($candidatures as $candidature): ?>
                            <?php
                            $statut = $candidature['statut'] ?? 'INCONNU';

                            $classes = [
                                'EN_ATTENTE' => 'bg-warning text-dark',
                                'PRESELECTIONNEE' => 'bg-success',
                                'REFUSEE' => 'bg-danger'
                            ];

                            $classe = $classes[$statut] ?? 'bg-secondary';
                            ?>
                            <tr>
                                <td>
                                    #<?= esc($candidature['id_candidature']) ?>
                                </td>
                                <td>
                                    Candidat #<?= esc($candidature['id_candidat']) ?>
                                </td>
                                <td>
                                    <?= esc($candidature['date_candidature'] ?? 'Non renseignée') ?>
                                </td>
                                <td>
                                    <span class="badge <?= esc($classe) ?>">
                                        <?= esc($statut) ?>
                                    </span>
                                </td>
                                <td>
                                    <a class="btn btn-sm btn-primary"
                                       href="<?= site_url('rh/candidatures/' . $candidature['id_candidature']) ?>">
                                        Consulter
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
