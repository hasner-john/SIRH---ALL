<style>body{font-family:DejaVu Sans,sans-serif;font-size:12px;line-height:1.6;margin:30px}h2{text-align:center;margin:30px 0}</style>
<h3><?= esc($entreprise->raisonSociale) ?></h3><div><?= esc($entreprise->adresse) ?> — STAT <?= esc($entreprise->stat) ?> — CIF <?= esc($entreprise->cif) ?></div>
<h2><?= esc($titre) ?></h2><p style="text-align:center">Référence : <?= esc($a['reference']) ?></p>
<p>Nous soussignés, certifions que <strong><?= esc($e['prenom'] . ' ' . $e['nom']) ?></strong>, matricule <?= esc($e['matricule']) ?><?= $e['numero_cnaps'] ? ', N° CNaPS ' . esc($e['numero_cnaps']) : '' ?>,
a été employé(e) dans notre entreprise du <strong><?= esc($e['date_embauche']) ?></strong> au <strong><?= esc($r['date_sortie']) ?></strong><?= $fonction ? ' en qualité de ' . esc($fonction) : '' ?><?= $e['categorie'] ? ' (catégorie ' . esc($e['categorie']) . ')' : '' ?>.</p>
<?php if ($a['type_attestation'] === 'CERTIFICAT_TRAVAIL'): ?><p>L'intéressé(e) nous quitte libre de tout engagement. Le présent certificat lui est délivré pour servir et valoir ce que de droit.</p>
<?php else: ?><p>Cette attestation est délivrée pour servir et valoir ce que de droit auprès de l'organisme concerné.</p><?php endif; ?>
<p style="margin-top:50px">Fait le <?= esc(date('d/m/Y')) ?><br><br>La Direction</p>
