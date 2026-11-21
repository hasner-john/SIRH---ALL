<?= $this->extend($layout) ?>
<?= $this->section('content') ?>
<?php if ($layout !== 'rh/layout'): ?><link href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>" rel="stylesheet"><?php endif; ?>
<h1 class="h3 mb-3"><?= esc($titre) ?></h1>
<form method="post" action="<?= site_url($prefixe . '/employes/creer') ?>" class="bg-white border rounded p-3">
  <?= csrf_field() ?>
  <h2 class="h5">1. La personne</h2>
  <div class="mb-2">
    <label class="me-3"><input type="radio" name="mode_personne" value="EXISTANTE" checked> Personne existante</label>
    <label><input type="radio" name="mode_personne" value="NOUVELLE"> Nouvelle personne</label>
  </div>
  <div id="p_ex" class="mb-3"><select class="form-select" name="id_personne"><option value="">Choisir…</option>
    <?php foreach ($personnes as $p): ?><option value="<?= (int) $p['id_personne'] ?>"><?= esc($p['nom'] . ' ' . $p['prenom'] . ' — ' . $p['email']) ?></option><?php endforeach; ?></select></div>
  <div id="p_new" class="row g-2 mb-3" style="display:none">
    <div class="col-md-4"><input class="form-control" type="email" name="email" placeholder="Email"></div>
    <div class="col-md-4"><input class="form-control" type="password" name="mot_de_passe" placeholder="Mot de passe temporaire (8 car. min)"></div>
    <div class="col-md-4"><input class="form-control" name="telephone" placeholder="Téléphone"></div>
    <div class="col-md-4"><input class="form-control" name="nom" placeholder="Nom"></div>
    <div class="col-md-4"><input class="form-control" name="prenom" placeholder="Prénom"></div>
    <div class="col-md-4"><input class="form-control" type="date" name="date_naissance"></div>
    <div class="col-12"><input class="form-control" name="adresse" placeholder="Adresse"></div>
  </div>

  <h2 class="h5">2. Le contrat</h2>
  <div class="mb-2">
    <label class="me-3"><input type="radio" name="mode_contrat" value="EXISTANT" checked> Contrat signé existant</label>
    <label><input type="radio" name="mode_contrat" value="NOUVEAU"> Créer un contrat</label>
  </div>
  <div id="c_ex" class="mb-3"><select class="form-select" name="id_contrat"><option value="">Choisir…</option>
    <?php foreach ($contrats as $c): ?><option value="<?= (int) $c['id_contrat'] ?>"><?= esc($c['reference'] . ' — ' . $c['type_contrat'] . ' — ' . number_format((float) $c['salaire'], 0, ',', ' ') . ' Ar') ?></option><?php endforeach; ?></select></div>
  <div id="c_new" class="row g-2 mb-3" style="display:none">
    <div class="col-md-2"><select class="form-select" name="type_contrat"><option>CDI</option><option>CDD</option><option>CE</option></select></div>
    <div class="col-md-3"><input class="form-control" type="date" name="date_debut" title="Début"></div>
    <div class="col-md-3"><input class="form-control" type="date" name="date_fin" title="Fin (obligatoire CDD/CE)"></div>
    <div class="col-md-2"><input class="form-control" type="number" min="0" step="0.01" name="salaire" placeholder="Salaire"></div>
    <div class="col-md-2"><input class="form-control" name="periodicite_salaire" value="MENSUEL"></div>
    <div class="col-md-6"><input class="form-control" name="lieu_travail" placeholder="Lieu de travail"></div>
    <div class="col-md-6"><input class="form-control" name="horaires" placeholder="Horaires"></div>
    <div class="col-md-6"><textarea class="form-control" name="missions" placeholder="Missions"></textarea></div>
    <div class="col-md-6"><textarea class="form-control" name="conges_absences" placeholder="Congés et absences"></textarea></div>
  </div>
  <div class="mb-3"><select class="form-select" name="id_poste"><option value="">Poste…</option>
    <?php foreach ($postes as $p): ?><option value="<?= (int) $p['id_poste'] ?>"><?= esc($p['libelle']) ?></option><?php endforeach; ?></select></div>

  <h2 class="h5">3. L'employé</h2>
  <div class="row g-2 mb-3">
    <div class="col-md-3"><select class="form-select" name="id_departement" required><option value="">Département…</option>
      <?php foreach ($departements as $d): ?><option value="<?= (int) $d['id_departement'] ?>"><?= esc($d['nom']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-3"><input class="form-control" name="fonction" placeholder="Fonction" required></div>
    <div class="col-md-2"><input class="form-control" name="categorie" placeholder="Catégorie (1A, 4A…)"></div>
    <div class="col-md-2"><select class="form-select" name="secteur"><option>NON_AGRICOLE</option><option>AGRICOLE</option></select></div>
    <div class="col-md-2"><input class="form-control" name="numero_cnaps" placeholder="N° CNaPS"></div>
    <div class="col-md-3"><select class="form-select" name="id_regime"><?php foreach ($regimes as $r): ?><option value="<?= (int) $r['id_regime'] ?>"><?= esc($r['libelle']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-3"><input class="form-control" type="date" name="date_embauche" title="Date d'embauche (défaut : début du contrat)"></div>
  </div>
  <button class="btn btn-primary">Embaucher</button>
</form>
<script>
document.querySelectorAll('[name=mode_personne]').forEach(r => r.addEventListener('change', () => {
  const n = document.querySelector('[name=mode_personne]:checked').value === 'NOUVELLE';
  document.getElementById('p_new').style.display = n ? '' : 'none'; document.getElementById('p_ex').style.display = n ? 'none' : '';
}));
document.querySelectorAll('[name=mode_contrat]').forEach(r => r.addEventListener('change', () => {
  const n = document.querySelector('[name=mode_contrat]:checked').value === 'NOUVEAU';
  document.getElementById('c_new').style.display = n ? '' : 'none'; document.getElementById('c_ex').style.display = n ? 'none' : '';
}));
</script>
<?= $this->endSection() ?>
