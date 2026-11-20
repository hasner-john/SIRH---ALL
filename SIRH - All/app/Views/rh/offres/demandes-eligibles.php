
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demandes éligibles - Espace RH</title>

    <link
        href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>"
        rel="stylesheet"
    >
</head>
<body class="bg-light">
<a class="btn btn-outline-danger position-fixed top-0 end-0 m-3" href="<?= site_url('deconnexion') ?>">Déconnexion</a>

    <div class="container py-4">
        <h2>Demandes de recrutement éligibles</h2>

        <p class="text-muted">
            Sélectionnez une demande validée pour créer une offre d'emploi.
        </p>

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

        <?php if (empty($demandes)): ?>
            <div class="alert alert-info">
                Aucune demande de recrutement n'est actuellement éligible.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped bg-white">
                    <thead class="table-dark">
                        <tr>
                            <th>Poste</th>
                            <th>Département</th>
                            <th>Effectif</th>
                            <th>Salaire par personne</th>
                            <th>Date du besoin</th>
                            <th>Motif</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($demandes as $demande): ?>
                            <tr>
                                <td><?= esc($demande['poste']) ?></td>
                                <td><?= esc($demande['departement']) ?></td>
                                <td><?= esc($demande['effectif_demande']) ?></td>
                                <td><?= esc($demande['salaire_par_personne']) ?></td>
                                <td><?= esc($demande['date_besoin']) ?></td>
                                <td><?= esc($demande['motif']) ?></td>
                                <td>
                                    <a
                                        href="<?= site_url('rh/offres/creer/' . $demande['id_demande']) ?>"
                                        class="btn btn-primary btn-sm"
                                    >
                                        Créer une offre
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <a href="<?= site_url('rh/offres') ?>" class="btn btn-secondary">
            Voir les offres
        </a>

        <a href="<?= site_url('rh') ?>" class="btn btn-outline-secondary">
            Retour à l'espace RH
        </a>
    </div>

</body>
</html>
