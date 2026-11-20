<?= $this->extend('rh/layout') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?= esc($titre) ?></h1>
    <a class="btn btn-outline-secondary" href="<?= site_url('rh/employes') ?>">Retour</a>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card"><div class="card-body">
            <h2 class="h5">Identité et coordonnées</h2>
            <dl class="row mb-0">
                <dt class="col-sm-5">Matricule</dt><dd class="col-sm-7"><?= esc($dossier['matricule']) ?></dd>
                <dt class="col-sm-5">Nom complet</dt><dd class="col-sm-7"><?= esc($dossier['prenom'] . ' ' . $dossier['nom']) ?></dd>
                <dt class="col-sm-5">Email</dt><dd class="col-sm-7"><?= esc($dossier['email']) ?></dd>
                <dt class="col-sm-5">Téléphone</dt><dd class="col-sm-7"><?= esc($dossier['telephone'] ?: '—') ?></dd>
                <dt class="col-sm-5">Adresse</dt><dd class="col-sm-7"><?= esc($dossier['adresse'] ?: '—') ?></dd>
                <dt class="col-sm-5">Statut</dt><dd class="col-sm-7"><?= esc($dossier['statut']) ?></dd>
            </dl>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card"><div class="card-body">
            <h2 class="h5">Contrat actuel</h2>
            <dl class="row mb-0">
                <dt class="col-sm-5">Référence</dt><dd class="col-sm-7"><?= esc($dossier['reference']) ?></dd>
                <dt class="col-sm-5">Type</dt><dd class="col-sm-7"><?= esc($dossier['type_contrat']) ?></dd>
                <dt class="col-sm-5">Période</dt><dd class="col-sm-7"><?= esc($dossier['date_debut']) ?> → <?= esc($dossier['date_fin'] ?: 'indéterminée') ?></dd>
                <dt class="col-sm-5">Salaire</dt><dd class="col-sm-7"><?= esc($dossier['salaire']) ?> <?= esc($dossier['periodicite_salaire'] ?: '') ?></dd>
                <dt class="col-sm-5">Lieu</dt><dd class="col-sm-7"><?= esc($dossier['lieu_travail'] ?: '—') ?></dd>
            </dl>
        </div></div>
    </div>
</div>

<h2 class="h4 mt-4">Affectations</h2>
<div class="table-responsive"><table class="table table-sm">
    <thead><tr><th>Fonction</th><th>Département</th><th>Début</th><th>Fin</th></tr></thead>
    <tbody><?php foreach ($affectations as $affectation): ?><tr>
        <td><?= esc($affectation['fonction'] ?: '—') ?></td>
        <td><?= esc($affectation['departement_nom']) ?></td>
        <td><?= esc($affectation['date_debut']) ?></td>
        <td><?= esc($affectation['date_fin'] ?: 'En cours') ?></td>
    </tr><?php endforeach; ?></tbody>
</table></div>

<h2 class="h4 mt-4">Historique salarial</h2>
<div class="table-responsive"><table class="table table-sm">
    <thead><tr><th>Date d'effet</th><th>Ancien salaire</th><th>Nouveau salaire</th><th>Motif</th></tr></thead>
    <tbody><?php foreach ($salaires as $salaire): ?><tr>
        <td><?= esc($salaire['date_effet']) ?></td>
        <td><?= esc($salaire['ancien_salaire'] ?? '—') ?></td>
        <td><?= esc($salaire['nouveau_salaire']) ?> <?= esc($salaire['periodicite_salaire'] ?: '') ?></td>
        <td><?= esc($salaire['motif'] ?: '—') ?></td>
    </tr><?php endforeach; ?></tbody>
</table></div>

<h2 class="h4 mt-4">Documents administratifs</h2>
<form class="row g-2 mb-3" method="post" enctype="multipart/form-data" action="<?= site_url('rh/employes/' . $dossier['id_employe'] . '/documents') ?>">
    <div class="col-md-2"><input class="form-control" name="type_document" placeholder="Type" required></div>
    <div class="col-md-3"><input class="form-control" type="file" name="document" required></div>
    <div class="col-md-2"><input class="form-control" type="date" name="date_expiration"></div>
    <div class="col-md-3"><input class="form-control" name="commentaire" placeholder="Commentaire"></div>
    <div class="col-md-2"><button class="btn btn-primary" type="submit">Ajouter</button></div>
</form>
<div class="table-responsive"><table class="table table-sm">
    <thead><tr><th>Type</th><th>Fichier</th><th>Expiration</th><th></th></tr></thead>
    <tbody><?php foreach ($documents as $document): ?><tr>
        <td><?= esc($document['type_document']) ?></td>
        <td><?= esc($document['nom_fichier']) ?></td>
        <td><?= esc($document['date_expiration'] ?: 'Sans expiration') ?></td>
        <td><a class="btn btn-sm btn-outline-primary" href="<?= site_url('rh/employes/' . $dossier['id_employe'] . '/documents/' . $document['id_document']) ?>">Télécharger</a></td>
    </tr><?php endforeach; ?></tbody>
</table></div>

<h2 class="h4 mt-4">Ajouter une évolution salariale</h2>
<form class="row g-2 mb-3" method="post" action="<?= site_url('rh/employes/' . $dossier['id_employe'] . '/salaires') ?>">
    <div class="col-md-2"><input class="form-control" type="number" min="0" step="0.01" name="nouveau_salaire" placeholder="Montant" required></div>
    <div class="col-md-2"><select class="form-select" name="periodicite_salaire"><option>MENSUEL</option><option>ANNUEL</option><option>HORAIRE</option></select></div>
    <div class="col-md-2"><input class="form-control" type="date" name="date_effet" required></div>
    <div class="col-md-4"><input class="form-control" name="motif" placeholder="Motif"></div>
    <div class="col-md-2"><button class="btn btn-primary" type="submit">Enregistrer</button></div>
</form>
<?= $this->endSection() ?>
