<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Espace RH</title>
  <link href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>" rel="stylesheet">
<style>
    .calendar-grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 1px; background: #dee2e6; }
    .calendar-grid > div { background: #fff; min-height: 92px; padding: .4rem; }
    .calendar-heading > div { min-height: auto; text-align: center; font-size: .8rem; font-weight: 600; color: #6c757d; padding: .5rem; }
    .calendar-day.outside-month { background: #f8f9fa; color: #adb5bd; }
    .calendar-event { border-radius: .2rem; color: #fff; font-size: .7rem; margin-top: .25rem; padding: .15rem .25rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .conge-event, .legend.conge-event { background: #0d6efd; }
    .absence-event, .legend.absence-event { background: #dc3545; }
    .legend { display: inline-block; width: .75rem; height: .75rem; border-radius: 50%; vertical-align: middle; }
    @media (max-width: 576px) { .calendar-grid > div { min-height: 72px; padding: .25rem; } .calendar-event { font-size: .6rem; } }
</style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand navbar-dark bg-dark mb-4">
  <div class="container">
    <span class="navbar-brand">Espace RH</span>
    <div class="navbar-nav">
      <a class="nav-link" href="<?= site_url('rh/evaluations') ?>">Évaluations</a>
      <a class="nav-link" href="<?= site_url('rh/entretiens') ?>">Entretiens</a>
      <a class="nav-link" href="<?= site_url('rh/tests') ?>">Tests techniques</a>
      <a class="nav-link" href="<?= site_url('rh/requetes') ?>">Requêtes candidats</a>
      <a class="nav-link" href="<?= site_url('rh/employes') ?>">Dossiers salariés</a>
      <a class="nav-link" href="<?= site_url('rh/temps-travail') ?>">Pointage</a>
      <a class="nav-link" href="<?= site_url('rh/conges') ?>">Congés</a>
      <a class="nav-link" href="<?= site_url('rh/modules-avances') ?>">Paie & avancés</a>
      <a class="nav-link ms-auto text-warning" href="<?= site_url('deconnexion') ?>">Déconnexion</a>
    </div>
  </div>
</nav>
<main class="container pb-5">
  <?php if (session()->getFlashdata('succes') || session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= esc(session()->getFlashdata('succes') ?: session()->getFlashdata('success')) ?></div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('erreur') || session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= esc(session()->getFlashdata('erreur') ?: session()->getFlashdata('error')) ?></div>
  <?php endif; ?>
  <?= $this->renderSection('content') ?>
</main>
<script src="<?= base_url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
