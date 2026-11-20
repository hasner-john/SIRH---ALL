<?= $this->extend('rh/layout') ?>

<?= $this->section('content') ?>
<h1 class="mb-4"><?= esc($titre) ?></h1>

<?php if ($employes === []): ?>
    <div class="alert alert-info">Aucun salarié n'est encore enregistré.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Matricule</th>
                    <th>Salarié</th>
                    <th>Poste / service</th>
                    <th>Contrat</th>
                    <th>Statut</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($employes as $employe): ?>
                <tr>
                    <td><?= esc($employe['matricule']) ?></td>
                    <td><?= esc($employe['prenom'] . ' ' . $employe['nom']) ?><br>
                        <small><?= esc($employe['email']) ?></small></td>
                    <td><?= esc($employe['fonction'] ?: 'Non renseigné') ?><br>
                        <small><?= esc($employe['departement_nom'] ?: 'Non renseigné') ?></small></td>
                    <td><?= esc($employe['type_contrat']) ?></td>
                    <td><span class="badge text-bg-secondary"><?= esc($employe['statut']) ?></span></td>
                    <td><a class="btn btn-sm btn-primary" href="<?= site_url('rh/employes/' . $employe['id_employe']) ?>">Consulter</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
