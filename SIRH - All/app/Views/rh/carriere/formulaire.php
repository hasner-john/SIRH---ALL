<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3"><?= esc($titre) ?></h1>
<form method="get" class="row g-2 mb-3"><div class="col-md-4"><select class="form-select" name="employe" onchange="this.form.submit()"><option value="">Choisir l'employé…</option>
  <?php foreach ($employes as $e): ?><option value="<?= (int) $e['id_employe'] ?>" <?= $idEmploye === (int) $e['id_employe'] ? 'selected' : '' ?>><?= esc($e['matricule'] . ' — ' . $e['prenom'] . ' ' . $e['nom']) ?></option><?php endforeach; ?></select></div></form>
<?php if ($actuel): ?>
<div class="alert alert-secondary">Situation actuelle : poste <strong><?= esc($actuel['poste'] ?? '—') ?></strong> — fonction <?= esc($actuel['fonction'] ?? '—') ?> — département <?= esc($actuel['departement'] ?? '—') ?> — salaire <strong><?= number_format((float) $actuel['salaire'], 0, ',', ' ') ?> Ar</strong> — contrat <?= esc($actuel['type_contrat']) ?></div>
<?php if ($prop): ?><div class="alert alert-info">Proposition : <?= esc($prop['justification']) ?></div><?php endif; ?>
<form method="post" action="<?= site_url('rh/carriere/mouvement') ?>" class="bg-white border rounded p-3 row g-2"><?= csrf_field() ?>
  <input type="hidden" name="id_employe" value="<?= (int) $actuel['id_employe'] ?>"><input type="hidden" name="id_proposition" value="<?= $prop ? (int) $prop['id_proposition_carriere'] : '' ?>"><input type="hidden" name="ancien_affiche" value="<?= esc($actuel['salaire']) ?>">
  <div class="col-md-3"><select class="form-select" name="type_mouvement" id="type"><?php foreach (['PROMOTION', 'REVISION_SALAIRE', 'CHANGEMENT_POSTE', 'MUTATION', 'CHANGEMENT_STATUT', 'AFFECTATION'] as $t): ?><option <?= $typePropose === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><input class="form-control" type="date" name="date_effet" required></div>
  <div class="col-md-6"><input class="form-control" name="motif" placeholder="Motif (obligatoire)" required></div>
  <div class="col-md-3 f-fonction"><input class="form-control" name="nouveau_poste" placeholder="Nouvelle fonction" value="<?= esc($prop['fonction_suggeree'] ?? '') ?>"></div>
  <div class="col-md-3 f-salaire"><input class="form-control" type="number" min="0" step="0.01" name="nouveau_salaire" placeholder="Nouveau salaire" value="<?= esc($prop['salaire_suggere'] ?? '') ?>"></div>
  <div class="col-md-3 f-poste"><select class="form-select" name="nouveau_id_poste"><option value="">Nouveau poste…</option><?php foreach ($postes as $p): ?><option value="<?= (int) $p['id_poste'] ?>" <?= $prop && (int) $prop['id_poste_suggere'] === (int) $p['id_poste'] ? 'selected' : '' ?>><?= esc($p['libelle']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3 f-dep"><select class="form-select" name="id_departement"><option value="">Département…</option><?php foreach ($departements as $d): ?><option value="<?= (int) $d['id_departement'] ?>"><?= esc($d['nom']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3 f-statut"><select class="form-select" name="nouveau_type_contrat"><option value="">Nouveau type de contrat…</option><option>CDI</option><option>CDD</option><option>CE</option></select></div>
  <div class="col-md-3 f-statut"><input class="form-control" type="date" name="date_fin" title="Fin du nouveau contrat (CDD/CE)"></div>
  <div class="col-12"><button class="btn btn-primary">Enregistrer</button> <small class="text-muted">Date future : le mouvement est planifié, sinon appliqué immédiatement.</small></div>
</form>
<script>
const MAP = {PROMOTION:['fonction','salaire','poste'], REVISION_SALAIRE:['salaire'], CHANGEMENT_POSTE:['poste','fonction','salaire'], MUTATION:['dep','poste'], CHANGEMENT_STATUT:['statut','salaire'], AFFECTATION:['dep','fonction']};
function maj(){const t=document.getElementById('type').value;['fonction','salaire','poste','dep','statut'].forEach(k=>document.querySelectorAll('.f-'+k).forEach(e=>e.style.display=(MAP[t]||[]).includes(k)?'':'none'));}
document.getElementById('type').addEventListener('change',maj);maj();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
