<?= $this->extend('direction/_layout') ?>
<?= $this->section('content') ?>

<h1>Demandes de recrutement</h1>

<div style="margin-bottom:16px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
    <strong style="color:#6e84a3;">Filtrer :</strong>
    <a class="btn <?= $statut === null ? 'btn-primary' : 'btn-secondary' ?>" href="/direction/demandes">
        Toutes (<?= $comptes['TOUTES'] ?? 0 ?>)
    </a>
    <?php foreach ($statuts as $s): ?>
        <a class="btn <?= $statut === $s ? 'btn-primary' : 'btn-secondary' ?>" href="/direction/demandes?statut=<?= $s ?>">
            <?= esc($s) ?> (<?= $comptes[$s] ?? 0 ?>)
        </a>
    <?php endforeach; ?>
</div>

<div class="card">
<?php if (empty($demandes)): ?>
    <div style="text-align:center;color:#6e84a3;padding:24px;">
        Aucune demande<?= $statut ? ' pour ce statut' : '' ?>.
    </div>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Poste</th><th>Département</th><th>Demandeur</th><th>Effectif</th>
                <th>Date besoin</th><th>Statut</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $couleurs = [
            'EN_ATTENTE' => '#f6c343',
            'VALIDE'     => '#00d97e',
            'REFUSEE'    => '#e63757',
            'REFUSE'     => '#e63757',
            'ANNULEE'    => '#6e84a3',
        ];
        
        foreach ($demandes as $d):
            $bg = $couleurs[$d['statut']] ?? '#6e84a3';
        ?>
            <tr>
                <td><strong><?= esc($d['poste']) ?></strong></td>
                <td><?= esc($d['departement']) ?></td>
                <td><?= esc(trim(($d['prenom_demandeur'] ?? '') . ' ' . ($d['nom_demandeur'] ?? '')) ?: '-') ?></td>
                <td><?= (int) $d['effectif_demande'] ?></td>
                <td><?= esc($d['date_besoin'] ?? '—') ?></td>
                <td>
                    <span class="badge" style="background:<?= $bg ?>;color:<?= $d['statut'] === 'EN_ATTENTE' ? '#333' : '#fff' ?>;">
                        <?= esc($d['statut']) ?>
                    </span>
                </td>
                <td><a class="btn btn-primary" href="/direction/demandes/<?= (int) $d['id_demande'] ?>">Détail</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</div>

<?= $this->endSection() ?>