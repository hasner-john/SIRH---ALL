<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<?php
$mois = (int) ($_GET['mois'] ?? date('n'));
$annee = (int) ($_GET['annee'] ?? date('Y'));
if ($mois < 1 || $mois > 12) {
    $mois = (int) date('n');
}
$premierJour = new DateTimeImmutable(sprintf('%04d-%02d-01', $annee, $mois));
$debutSemaine = $premierJour->modify('monday this week');
$finMois = $premierJour->modify('last day of this month');
$finSemaine = $finMois->modify('sunday this week');
$moisPrecedent = $premierJour->modify('-1 month');
$moisSuivant = $premierJour->modify('+1 month');
$evenementsParJour = [];
foreach ($evenements as $evenement) {
    $debut = new DateTimeImmutable($evenement['date_debut']);
    $fin = new DateTimeImmutable($evenement['date_fin']);
    for ($jour = $debut; $jour <= $fin; $jour = $jour->modify('+1 day')) {
        $evenementsParJour[$jour->format('Y-m-d')][] = $evenement;
    }
}
$nomsMois = [1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'];
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1">Congés et absences</h1>
        <p class="text-muted mb-0">Demandes, absences et calendrier du personnel.</p>
    </div>
    <a class="btn btn-outline-primary" href="<?= site_url('rh/conges') ?>?mois=<?= $mois ?>&annee=<?= $annee ?>#calendrier">Voir le calendrier</a>
</div>

<?php if ($message = session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc($message) ?></div><?php endif ?>
<?php if ($message = session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc($message) ?></div><?php endif ?>

<div class="row g-4">
    <div class="col-xl-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><h2 class="h5 mb-0">Nouvelle demande de congé</h2></div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data" action="<?= site_url('rh/conges') ?>" class="row g-3">
                    <?= csrf_field() ?>
                    <div class="col-md-4"><label class="form-label">ID employé</label><input class="form-control" type="number" name="id_employe" required></div>
                    <div class="col-md-8"><label class="form-label">Type de congé</label><select class="form-select" name="id_type_conge" required><?php foreach ($types as $type): ?><option value="<?= esc($type['id_type_conge']) ?>"><?= esc($type['libelle']) ?></option><?php endforeach ?></select></div>
                    <div class="col-md-4"><label class="form-label">Du</label><input class="form-control" type="date" name="date_debut" required></div>
                    <div class="col-md-4"><label class="form-label">Au</label><input class="form-control" type="date" name="date_fin" required></div>
                    <div class="col-md-4"><label class="form-label">Nombre de jours</label><input class="form-control" type="number" step="0.5" min="0.5" name="nombre_jours" required></div>
                    <div class="col-md-6"><label class="form-label">Motif</label><textarea class="form-control" name="motif" rows="2"></textarea></div>
                    <div class="col-md-6"><label class="form-label">Justificatif</label><input class="form-control" type="file" name="justificatif"></div>
                    <div class="col-12"><button class="btn btn-primary" type="submit">Soumettre la demande</button></div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><h2 class="h5 mb-0">Déclarer une absence</h2></div>
            <div class="card-body">
                <form method="post" action="<?= site_url('rh/absences') ?>" class="row g-3">
                    <?= csrf_field() ?>
                    <div class="col-12"><label class="form-label">ID employé</label><input class="form-control" type="number" name="id_employe" required></div>
                    <div class="col-6"><label class="form-label">Du</label><input class="form-control" type="date" name="date_debut" required></div>
                    <div class="col-6"><label class="form-label">Au</label><input class="form-control" type="date" name="date_fin" required></div>
                    <div class="col-12"><label class="form-label">Type</label><input class="form-control" name="type_absence" value="ABSENCE" required></div>
                    <div class="col-12"><input class="form-control" name="motif" placeholder="Motif"></div>
                    <div class="col-12 form-check ms-2"><input class="form-check-input" type="checkbox" name="justifiee" value="1" id="justifiee"><label class="form-check-label" for="justifiee">Absence justifiée</label></div>
                    <div class="col-12"><button class="btn btn-outline-primary" type="submit">Enregistrer l'absence</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="calendrier" class="card shadow-sm mt-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <a class="btn btn-sm btn-outline-secondary" href="?mois=<?= $moisPrecedent->format('n') ?>&annee=<?= $moisPrecedent->format('Y') ?>#calendrier">‹</a>
        <h2 class="h5 mb-0"><?= esc($nomsMois[$mois] . ' ' . $annee) ?></h2>
        <a class="btn btn-sm btn-outline-secondary" href="?mois=<?= $moisSuivant->format('n') ?>&annee=<?= $moisSuivant->format('Y') ?>#calendrier">›</a>
    </div>
    <div class="card-body p-2">
        <div class="calendar-grid calendar-heading"><?php foreach (['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] as $nomJour): ?><div><?= $nomJour ?></div><?php endforeach ?></div>
        <div class="calendar-grid">
        <?php for ($jour = $debutSemaine; $jour <= $finSemaine; $jour = $jour->modify('+1 day')): ?>
            <?php $date = $jour->format('Y-m-d'); $horsMois = $jour->format('m') !== sprintf('%02d', $mois); ?>
            <div class="calendar-day <?= $horsMois ? 'outside-month' : '' ?>">
                <div class="small fw-semibold"><?= $jour->format('j') ?></div>
                <?php foreach ($evenementsParJour[$date] ?? [] as $evenement): ?>
                    <div class="calendar-event <?= $evenement['type_evenement'] === 'ABSENCE' ? 'absence-event' : 'conge-event' ?>" title="<?= esc($evenement['libelle']) ?>">
                        <?= esc($evenement['matricule'] . ' · ' . $evenement['libelle']) ?>
                    </div>
                <?php endforeach ?>
            </div>
        <?php endfor ?>
        </div>
        <div class="small text-muted mt-3"><span class="legend conge-event"></span> Congé <span class="legend absence-event ms-3"></span> Absence</div>
    </div>
</div>

<div class="card shadow-sm mt-4">
    <div class="card-header bg-white"><h2 class="h5 mb-0">Demandes à traiter</h2></div>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Employé</th><th>Type</th><th>Période</th><th>Statut</th><th>Action</th></tr></thead>
        <tbody><?php foreach ($demandes as $demande): ?><tr>
            <td><?= esc($demande['matricule'] . ' - ' . $demande['prenom'] . ' ' . $demande['nom']) ?></td>
            <td><?= esc($demande['type_libelle']) ?></td><td><?= esc($demande['date_debut'] . ' → ' . $demande['date_fin']) ?></td>
            <td><span class="badge text-bg-<?= $demande['statut'] === 'VALIDE' ? 'success' : ($demande['statut'] === 'REFUSE' ? 'danger' : 'warning') ?>"><?= esc($demande['statut']) ?></span></td>
            <td><?php if ($demande['statut'] === 'EN_ATTENTE'): ?><form method="post" action="<?= site_url('rh/conges/' . $demande['id_demande_conge'] . '/decision') ?>" class="d-flex gap-1"><?= csrf_field() ?><button class="btn btn-sm btn-success" name="statut" value="VALIDE">Valider</button><button class="btn btn-sm btn-outline-danger" name="statut" value="REFUSE">Refuser</button></form><?php else: ?>-<?php endif ?></td>
        </tr><?php endforeach ?><?php if (! $demandes): ?><tr><td colspan="5" class="text-center text-muted py-4">Aucune demande.</td></tr><?php endif ?></tbody>
    </table></div>
</div>
<?= $this->endSection() ?>
