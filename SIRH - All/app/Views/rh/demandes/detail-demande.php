```php
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Détail d'une demande</title>

    <link href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>"
        rel="stylesheet">
</head>

<body class="bg-light">
<a class="btn btn-outline-danger position-fixed top-0 end-0 m-3" href="<?= site_url('deconnexion') ?>">Déconnexion</a>

    <div class="container py-4">

        <div class="mb-4">
            <a href="<?= site_url('rh/demandes') ?>"
                class="btn btn-outline-secondary btn-sm mb-3">
                &larr; Retour à la liste
            </a>

            <h1 class="h3">Détail de la demande</h1>
            <p class="text-muted">
                Consultation et traitement d'une demande de recrutement
            </p>
        </div>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success">
                <?= esc(session()->getFlashdata('success')) ?>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger">
                <?= esc(session()->getFlashdata('error')) ?>
            </div>
        <?php endif; ?>

        <?php
        $val = static function ($data, $key, $default = 'Non renseigné') {
            if (is_array($data)) {
                return $data[$key] ?? $default;
            }

            if (is_object($data)) {
                return $data->$key ?? $default;
            }

            return $default;
        };

        $id = $val($demande, 'id_demande', $val($demande, 'id', ''));
        $statut = $val($demande, 'statut', 'Inconnu');
        $poste = $val(
            $demande,
            'poste_libelle',
            $val($demande, 'libelle_poste', $val($demande, 'poste'))
        );

        $badge = 'secondary';

        if ($statut === 'EN_ATTENTE') {
            $badge = 'warning';
        } elseif ($statut === 'REFUSEE') {
            $badge = 'danger';
        } elseif ($statut === 'VALIDEE') {
            $badge = 'success';
        }
        ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Informations de la demande</h2>
            </div>

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-6">
                        <strong>Numéro de la demande</strong>
                        <p><?= esc($id) ?></p>
                    </div>

                    <div class="col-md-6">
                        <strong>Poste demandé</strong>
                        <p><?= esc($poste) ?></p>
                    </div>

                    <div class="col-md-6">
                        <strong>Statut actuel</strong>
                        <p>
                            <span class="badge text-bg-<?= $badge ?>">
                                <?= esc($statut) ?>
                            </span>
                        </p>
                    </div>

                    <div class="col-md-6">
                        <strong>Date de création</strong>
                        <p>
                            <?= esc($val(
                                $demande,
                                'date_creation',
                                $val($demande, 'created_at')
                            )) ?>
                        </p>
                    </div>

                    <div class="col-12">
                        <strong>Description</strong>
                        <p>
                            <?= nl2br(esc($val($demande, 'description'))) ?>
                        </p>
                    </div>

                </div>
            </div>
        </div>

        <?php if ($statut === 'BROUILLON'): ?>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Traiter la demande</h2>
                </div>

                <div class="card-body">
                    <p>
                        Choisissez si la demande doit être mise en attente
                        ou refusée, puis ajoutez un commentaire.
                    </p>

                    <form action="<?= site_url('rh/demandes/decision') ?>"
                        method="post">

                        <?= csrf_field() ?>

                        <input type="hidden"
                            name="id_demande"
                            value="<?= esc($id) ?>">

                        <div class="mb-3">
                            <label for="nouveau_statut" class="form-label">
                                Décision
                            </label>

                            <select name="nouveau_statut"
                                id="nouveau_statut"
                                class="form-select"
                                required>
                                <option value="">-- Choisir une décision --</option>
                                <option value="EN_ATTENTE">
                                    Mettre en attente
                                </option>
                                <option value="REFUSEE">
                                    Refuser la demande
                                </option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="commentaire" class="form-label">
                                Commentaire
                            </label>

                            <textarea name="commentaire"
                                id="commentaire"
                                class="form-control"
                                rows="4"
                                placeholder="Motif de la décision..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Enregistrer la décision
                        </button>
                    </form>
                </div>
            </div>

        <?php elseif ($statut === 'REFUSEE'): ?>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Réouvrir la demande</h2>
                </div>

                <div class="card-body">
                    <p>
                        Vous pouvez demander la réouverture de cette demande
                        pour la remettre en attente, si vous y êtes autorisé.
                    </p>

                    ```php
                    <form action="<?= site_url('rh/demandes/reouvrir') ?>"
                        method="post"
                        onsubmit="return confirm('Voulez-vous vraiment réouvrir cette demande ? Elle sera remise en attente.');">

                        <?= csrf_field() ?>

                        <input type="hidden"
                            name="id_demande"
                            value="<?= esc($id) ?>">

                        <div class="mb-3">
                            <label for="commentaire_reouverture" class="form-label">
                                Motif de la réouverture
                            </label>

                            <textarea name="commentaire"
                                id="commentaire_reouverture"
                                class="form-control"
                                rows="4"
                                
                                placeholder="Expliquez pourquoi la demande doit être réouverte..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-warning">
                            Réouvrir la demande
                        </button>
                    </form>
                  
                </div>
            </div>

        <?php else: ?>

            <div class="alert alert-info">
                Aucune action n'est disponible pour le statut actuel
                de cette demande.
            </div>

        <?php endif; ?>

    </div>

    <script src="<?= base_url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
</body>

</html>
```