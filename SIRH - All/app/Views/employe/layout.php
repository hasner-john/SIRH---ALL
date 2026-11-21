<!doctype html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= esc($titre ?? 'Espace employé') ?></title>
<link href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>" rel="stylesheet"></head>
<body class="bg-light">
<nav class="navbar navbar-expand navbar-dark bg-success mb-4"><div class="container">
  <span class="navbar-brand">Espace employé</span>
  <div class="navbar-nav">
    <a class="nav-link" href="<?= site_url('employe') ?>">Accueil</a><a class="nav-link" href="<?= site_url('employe/notifications') ?>">Notifications</a>
    <a class="nav-link" href="<?= site_url('employe/formations') ?>">Formations</a><a class="nav-link" href="<?= site_url('employe/discipline') ?>">Discipline</a>
    <a class="nav-link" href="<?= site_url('employe/bulletins') ?>">Bulletins</a>
    <a class="nav-link ms-auto text-warning" href="<?= site_url('deconnexion') ?>">Déconnexion</a>
  </div></div></nav>
<main class="container pb-5">
  <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>
  <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>
  <?= $this->renderSection('content') ?>
</main>
<script src="<?= base_url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
</body></html>
