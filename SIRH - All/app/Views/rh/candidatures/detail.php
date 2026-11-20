
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Détail de la candidature</title>
    <link href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>"
          rel="stylesheet">
</head>
<body class="bg-light">
<a class="btn btn-outline-danger position-fixed top-0 end-0 m-3" href="<?= site_url('deconnexion') ?>">Déconnexion</a>

<div class="container py-5">
    <a href="<?= site_url('rh/candidatures/offre/' . ($candidature['id_offre'] ?? '')) ?>"
       class="btn btn-outline-secondary mb-4">
        Retour aux candidatures
    </a>

    <h1 class="h3 mb-4">Détail de la candidature</h1>

    <?php if (session()->getFlashdata('succes')): ?>
        <div class="alert alert-success">
            <?= esc(session()->getFlashdata('succes')) ?>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('erreur')): ?>
        <div class="alert alert-danger">
            <?= esc(session()->getFlashdata('erreur')) ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-header">
            Informations du candidat
        </div>
        <div class="card-body">
            <p>
                <strong>Nom :</strong>
                <?= esc(($candidature['nom'] ?? '') . ' ' . ($candidature['prenom'] ?? '')) ?>
            </p>
            <p>
                <strong>Email :</strong>
                <?= esc($candidature['email'] ?? 'Non renseigné') ?>
            </p>
            <p>
                <strong>Téléphone :</strong>
                <?= esc($candidature['telephone'] ?? 'Non renseigné') ?>
            </p>
            <p class="mb-0">
                <strong>Statut :</strong>
                <span class="badge bg-secondary">
                    <?= esc($candidature['statut'] ?? 'INCONNU') ?>
                </span>
            </p>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header">
            Documents du candidat
        </div>
        <div class="card-body">
            <?php
            $documents = [
                'cv' => 'CV (PDF)',
                'lettre_motivation' => 'Lettre de motivation (PDF)',
                'cin' => 'Carte d’identité (PDF ou image)'
            ];
            ?>

            <ul class="list-group list-group-flush">
                <?php foreach ($documents as $type => $libelle): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= esc($libelle) ?></span>

                        <?php if (!empty($candidature[$type])): ?>
                            <a class="btn btn-sm btn-outline-primary"
                               href="<?= site_url('rh/candidatures/' . $candidature['id_candidature'] . '/document/' . $type) ?>"
                               target="_blank" rel="noopener">
                                Consulter
                            </a>
                        <?php else: ?>
                            <span class="text-danger">Absent</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <?php if (!in_array($candidature['statut'] ?? '', ['PRESELECTIONNEE', 'REFUSEE'], true)): ?>
        <div class="card shadow-sm">
            <div class="card-header">
                Décision RH
            </div>
            <div class="card-body">
                <form method="post"
                      action="<?= site_url('rh/candidatures/' . $candidature['id_candidature'] . '/decider') ?>">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="commentaire" class="form-label">
                            Commentaire (facultatif)
                        </label>
                        <textarea name="commentaire" id="commentaire"
                                  class="form-control" rows="3"></textarea>
                    </div>

                    <button type="submit" name="statut"
                            value="PRESELECTIONNEE"
                            class="btn btn-success"
                            onclick="return confirm('Présélectionner ce candidat ?')">
                        Présélectionner
                    </button>

                    <button type="submit" name="statut"
                            value="REFUSEE"
                            class="btn btn-danger"
                            onclick="return confirm('Refuser cette candidature ?')">
                        Refuser
                    </button>
                </form>
            </div>
        </div>
    <?php elseif (($candidature['statut'] ?? '') === 'PRESELECTIONNEE'): ?>
        <a class="btn btn-primary"
           href="<?= site_url('rh/candidatures/' . $candidature['id_candidature'] . '/entretien') ?>">
            Programmer un entretien
        </a>
    <?php endif; ?>
</div>

</body>
</html>
