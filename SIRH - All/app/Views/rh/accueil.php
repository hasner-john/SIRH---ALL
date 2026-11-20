<?= $this->extend('rh/layout') ?>

<?= $this->section('content') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h2 mb-1">Tableau de bord RH</h1>
        <p class="text-muted mb-0">Bienvenue dans votre espace de travail, <?= esc(session()->get('prenom') ?: 'équipe RH') ?>.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= site_url('deconnexion') ?>">Déconnexion</a>
</div>

<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['Demandes à traiter', $indicateurs['demandes'], 'rh/demandes', 'warning'],
        ['Candidatures à examiner', $indicateurs['candidatures'], 'rh/evaluations', 'primary'],
        ['Entretiens planifiés', $indicateurs['entretiens'], 'rh/entretiens', 'info'],
        ['Congés en attente', $indicateurs['conges'], 'rh/conges', 'success'],
        ['Salariés actifs', $indicateurs['employes'], 'rh/employes', 'dark'],
        ['Bulletins de paie', $indicateurs['bulletins'], 'rh/modules-avances', 'danger'],
        ['Incidents disciplinaires', $indicateurs['incidents'], 'rh/modules-avances', 'secondary'],
    ];
    ?>
    <?php foreach ($cards as [$label, $value, $link, $color]): ?>
        <div class="col-sm-6 col-lg">
            <a href="<?= site_url($link) ?>" class="text-decoration-none">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="text-muted small"><?= esc($label) ?></div>
                        <div class="display-6 fw-semibold text-<?= esc($color) ?>"><?= esc($value) ?></div>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><h2 class="h5 mb-0">Recrutement</h2></div>
            <div class="card-body">
                <p class="text-muted">Suivez le cycle des demandes, des offres et des candidatures.</p>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-primary" href="<?= site_url('rh/demandes') ?>">Demandes de recrutement</a>
                    <a class="btn btn-outline-primary" href="<?= site_url('rh/offres') ?>">Offres d'emploi</a>
                    <a class="btn btn-outline-primary" href="<?= site_url('rh/evaluations') ?>">Évaluations</a>
                    <a class="btn btn-outline-primary" href="<?= site_url('rh/requetes') ?>">Requêtes candidats</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><h2 class="h5 mb-0">Administration du personnel</h2></div>
            <div class="card-body">
                <p class="text-muted">Accédez au dossier salarié, au pointage et au calendrier RH.</p>
                <div class="d-grid gap-2">
                    <a class="btn btn-dark" href="<?= site_url('rh/employes') ?>">Dossiers salariés</a>
                    <a class="btn btn-outline-dark" href="<?= site_url('rh/temps-travail') ?>">Temps de travail et pointage</a>
                    <a class="btn btn-outline-dark" href="<?= site_url('rh/conges') ?>">Congés, absences et calendrier</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
