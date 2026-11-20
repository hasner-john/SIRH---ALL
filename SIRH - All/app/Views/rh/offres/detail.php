
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détail de l'offre - Espace RH</title>
    <link
        href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>"
        rel="stylesheet"
    >
</head>
<body class="bg-light">
<a class="btn btn-outline-danger position-fixed top-0 end-0 m-3" href="<?= site_url('deconnexion') ?>">Déconnexion</a>

<div class="container py-4">
    <h2>Détail de l'offre d'emploi</h2>

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

    <div class="card">
        <div class="card-header">
            Offre n° <?= esc($offre['id_offre']) ?>
        </div>

        <div class="card-body">
            <h4><?= esc($offre['titre']) ?></h4>

            <p>
                <strong>Statut :</strong>
                <?= esc($offre['statut']) ?>
            </p>

            <hr>

            <h5>Description de l'offre</h5>
            <div class="mb-4"><?= nl2br(esc($offre['description'])) ?></div>

            <h5>Informations sur le recrutement</h5>

            <p><strong>Poste :</strong> <?= esc($offre['poste']) ?></p>
            <p><strong>Département :</strong> <?= esc($offre['departement']) ?></p>
            <p><strong>Effectif demandé :</strong> <?= esc($offre['effectif_demande']) ?></p>
            <p><strong>Salaire par personne :</strong> <?= esc($offre['salaire_par_personne']) ?></p>
            <p><strong>Date du besoin :</strong> <?= esc($offre['date_besoin']) ?></p>
            <p><strong>Motif de la demande :</strong> <?= esc($offre['motif']) ?></p>

            <hr>

            <h5>Dates de l'offre</h5>

            <p>
                <strong>Création :</strong>
                <?= esc($offre['date_creation']) ?>
            </p>

            <p>
                <strong>Publication :</strong>
                <?= esc($offre['date_publication'] ?? 'Non publiée') ?>
            </p>

            <p>
                <strong>Date limite :</strong>
                <?= esc($offre['date_limite'] ?? 'Aucune date limite') ?>
            </p>
        </div>
    </div>

    <div class="mt-3">
        <a href="<?= site_url('rh/offres') ?>" class="btn btn-secondary">
            Retour à la liste
        </a>
    </div>
</div>

</body>
</html>
