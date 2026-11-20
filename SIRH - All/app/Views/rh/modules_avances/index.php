<?= $this->extend('rh/layout') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-4"><?= esc($titre) ?></h1>

<ul class="nav nav-tabs" role="tablist">
  <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#paie" type="button">Paie</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#formation" type="button">Évaluation & formation</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#carriere" type="button">Carrière</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#discipline" type="button">Discipline</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#sorties" type="button">Ruptures</button></li>
</ul>

<div class="tab-content border border-top-0 bg-white p-3 mb-5">
  <div class="tab-pane fade show active" id="paie">
    <form method="post" action="<?= site_url('rh/modules-avances/paie/calculer') ?>" class="row g-2 mb-3">
      <div class="col-md-4"><select class="form-select" name="id_periode" required>
        <?php foreach ($periodes as $periode): ?><option value="<?= (int)$periode['id_periode'] ?>"><?= esc(sprintf('%02d/%04d — %s', $periode['mois'], $periode['annee'], $periode['statut'])) ?></option><?php endforeach; ?>
      </select></div>
      <div class="col-md-auto"><button class="btn btn-primary">Calculer les bulletins</button></div>
      
      <div class="col-md-auto">
        <button class="btn btn-success" formaction="<?= site_url('rh/modules-avances/paie/valider') ?>">Valider la période</button>
      </div>
    </form>
    
      <a class="btn btn-outline-secondary mb-3" href="#" onclick="location='<?= site_url('rh/modules-avances/paie/etat') ?>/'+document.querySelector('[name=id_periode]').value;return false;">Exporter l'état de paie (Excel)</a>

      <form method="get" action="<?= site_url('rh/modules-avances/paie/consulter') ?>" class="row g-2 mb-3">
        <div class="col-md-3"><input class="form-control" type="date" name="date" required></div>
        <div class="col-md-auto"><button class="btn btn-outline-primary">Voir l'état de paie à cette date</button></div>
      </form>
    <div class="table-responsive"><table class="table table-sm">
      <thead><tr><th>Matricule</th><th>Salarié</th><th>Brut</th><th>CNaPS</th><th>OSTIE</th><th>IRSA</th><th>Net à payer</th><th>Statut</th><th>Action</th></tr></thead>
      <tbody><?php foreach ($bulletins as $bulletin): ?><tr>
        <td><?= esc($bulletin['matricule']) ?></td><td><?= esc($bulletin['prenom'].' '.$bulletin['nom']) ?></td>
        <td><?= number_format((float)$bulletin['salaire_brut'],0,',',' ') ?></td><td><?= number_format((float)$bulletin['cnaps_salarial'],0,',',' ') ?></td>
        <td><?= number_format((float)$bulletin['ostie_salarial'],0,',',' ') ?></td><td><?= number_format((float)$bulletin['irsa'],0,',',' ') ?></td>
        <td><strong><?= number_format((float)$bulletin['net_a_payer'],0,',',' ') ?> Ar</strong></td><td><?= esc($bulletin['statut']) ?></td>        
        <th>
          <a href="<?= site_url('rh/modules-avances/paie/bulletin/'.(int)$bulletin['id_bulletin']) ?>" target="_blank">PDF</a>
        </th>
      </tr>
      
      <?php endforeach; ?>
    </tbody>
    </table></div>
  </div>


  <div class="tab-pane fade" id="formation">
    <form method="post" action="<?= site_url('rh/modules-avances/formations') ?>" class="row g-2 mb-4">
      <div class="col-md-3"><input class="form-control" name="titre" placeholder="Titre de formation" required></div>
      <div class="col-md-3"><input class="form-control" name="prestataire" placeholder="Prestataire"></div>
      <div class="col-md-2"><input class="form-control" type="date" name="date_debut" required></div>
      <div class="col-md-2"><input class="form-control" type="date" name="date_fin" required></div>
      <div class="col-md-2"><input class="form-control" type="number" min="0" step="0.01" name="cout" value="0"></div>
      <div class="col-12"><textarea class="form-control" name="description" placeholder="Description"></textarea></div>
      <div class="col-12"><button class="btn btn-primary">Créer la formation</button></div>
    </form>
    <div class="row g-3"><?php foreach ($formations as $formation): ?><div class="col-md-4"><div class="card h-100"><div class="card-body">
      <h2 class="h6"><?= esc($formation['titre']) ?></h2><p class="small mb-1"><?= esc($formation['date_debut'].' → '.$formation['date_fin']) ?></p>
      <span class="badge text-bg-secondary"><?= esc($formation['statut']) ?></span>
    </div></div></div><?php endforeach; ?></div>
  </div>

  <div class="tab-pane fade" id="carriere">
    <form method="post" action="<?= site_url('rh/modules-avances/carriere') ?>" class="row g-2 mb-4">
      <div class="col-md-3"><select class="form-select" name="id_employe" required><?php foreach ($employes as $employe): ?><option value="<?= (int)$employe['id_employe'] ?>"><?= esc($employe['matricule'].' — '.$employe['prenom'].' '.$employe['nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><select class="form-select" name="type_mouvement"><option>PROMOTION</option><option>MUTATION</option><option>CHANGEMENT_STATUT</option><option>REVISION_SALAIRE</option><option>AFFECTATION</option></select></div>
      <div class="col-md-2"><input class="form-control" name="nouveau_poste" placeholder="Nouveau poste" required></div>
      <div class="col-md-2"><input class="form-control" type="date" name="date_effet" required></div>
      <div class="col-md-3"><input class="form-control" name="motif" placeholder="Motif"></div>
      <div class="col-12"><button class="btn btn-primary">Enregistrer le mouvement</button></div>
    </form>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Date</th><th>Salarié</th><th>Type</th><th>Nouveau poste</th><th>Motif</th></tr></thead><tbody>
      <?php foreach ($mouvements as $mouvement): ?><tr><td><?= esc($mouvement['date_effet']) ?></td><td><?= esc($mouvement['prenom'].' '.$mouvement['nom']) ?></td><td><?= esc($mouvement['type_mouvement']) ?></td><td><?= esc($mouvement['nouveau_poste'] ?: '—') ?></td><td><?= esc($mouvement['motif'] ?: '—') ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </div>

  <div class="tab-pane fade" id="discipline">
    <form method="post" action="<?= site_url('rh/modules-avances/incidents') ?>" class="row g-2 mb-4">
      <div class="col-md-3"><select class="form-select" name="id_employe" required><?php foreach ($employes as $employe): ?><option value="<?= (int)$employe['id_employe'] ?>"><?= esc($employe['matricule'].' — '.$employe['prenom'].' '.$employe['nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><input class="form-control" type="date" name="date_incident" required></div>
      <div class="col-md-3"><input class="form-control" name="objet" placeholder="Objet" required></div>
      <div class="col-md-4"><input class="form-control" name="description" placeholder="Description" required></div>
      <div class="col-12"><button class="btn btn-danger">Signaler l’incident</button></div>
    </form>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Date</th><th>Salarié</th><th>Objet</th><th>Statut</th></tr></thead><tbody>
      <?php foreach ($incidents as $incident): ?><tr><td><?= esc($incident['date_incident']) ?></td><td><?= esc($incident['prenom'].' '.$incident['nom']) ?></td><td><?= esc($incident['objet']) ?></td><td><?= esc($incident['statut']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <div class="row g-3 mt-2">
      <div class="col-lg-6"><form method="post" action="<?= site_url('rh/modules-avances/incidents/reponse') ?>" class="border rounded p-3">
        <h2 class="h6">Réponse de l’employé</h2>
        <input type="hidden" name="id_incident" value="<?= (int)($incidents[0]['id_incident'] ?? 0) ?>">
        <input class="form-control mb-2" type="date" name="date_reponse" value="<?= date('Y-m-d') ?>" required>
        <textarea class="form-control mb-2" name="texte" placeholder="Réponse contradictoire" required></textarea>
        <button class="btn btn-outline-danger">Enregistrer la réponse</button>
      </form></div>
      <div class="col-lg-6"><form method="post" action="<?= site_url('rh/modules-avances/incidents/sanction') ?>" class="border rounded p-3">
        <h2 class="h6">Décision / sanction</h2>
        <input type="hidden" name="id_incident" value="<?= (int)($incidents[0]['id_incident'] ?? 0) ?>">
        <select class="form-select mb-2" name="type_sanction"><option>AVERTISSEMENT</option><option>BLAME</option><option>MISE_A_PIED</option><option>LICENCEMENT</option><option>AUTRE</option></select>
        <input class="form-control mb-2" type="date" name="date_decision" value="<?= date('Y-m-d') ?>" required>
        <input class="form-control mb-2" type="date" name="date_effet" value="<?= date('Y-m-d') ?>" required>
        <input class="form-control mb-2" name="motif" placeholder="Motif de la sanction" required>
        <button class="btn btn-danger">Décider</button>
      </form></div>
    </div>
  </div>

  <div class="tab-pane fade" id="sorties">
    <form method="post" action="<?= site_url('rh/modules-avances/ruptures') ?>" class="row g-2 mb-4">
      <div class="col-md-3"><select class="form-select" name="id_employe" required><?php foreach ($employes as $employe): ?><option value="<?= (int)$employe['id_employe'] ?>"><?= esc($employe['matricule'].' — '.$employe['prenom'].' '.$employe['nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-3"><select class="form-select" name="motif"><option>DEMISSION</option><option>LICENCIEMENT_ECONOMIQUE</option><option>LICENCIEMENT_FAUTE</option><option>FIN_CDD</option><option>RETRAITE</option><option>RUPTURE_CONVENTIONNELLE</option><option>AUTRE</option></select></div>
      <div class="col-md-2"><input class="form-control" type="date" name="date_notification" required></div>
      <div class="col-md-2"><input class="form-control" type="date" name="date_sortie" required></div>
      <div class="col-md-2"><input class="form-control" name="motif_detail" placeholder="Détails"></div>
      <div class="col-12"><button class="btn btn-warning">Créer la rupture</button></div>
    </form>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Salarié</th><th>Motif</th><th>Sortie</th><th>Statut</th></tr></thead><tbody>
      <?php foreach ($ruptures as $rupture): ?><tr><td><?= esc($rupture['prenom'].' '.$rupture['nom']) ?></td><td><?= esc($rupture['motif']) ?></td><td><?= esc($rupture['date_sortie']) ?></td><td><?= esc($rupture['statut']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </div>
</div>
<?= $this->endSection() ?>
