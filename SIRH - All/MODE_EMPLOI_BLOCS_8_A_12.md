# Mode d'emploi — Blocs 8 à 12

Ce document explique l'utilisation du module **Paie, compétences, carrière,
discipline et offboarding** et les liens avec les données des blocs précédents.

Accès RH :

```text
http://localhost:8080/rh/modules-avances
```

Les actions du bloc Paie sont principalement réservées aux rôles RH et
Administrateur. Les rôles de démonstration utilisent le mot de passe
`password`.

---
## Bloc 8 — Paie et cotisations

### Données à préparer avant calcul

La paie utilise obligatoirement :

- un contrat actif dans `contrat`;
- un employé actif dans `employe`;
- un historique salarial dans `historique_salaire`;
- une période dans `periode_paie`;
- les paramètres de paie dans `parametre_paie`;
- le barème IRSA dans `bareme_irsa`;
- les rubriques dans `rubrique_paie`;
- les absences dans `absence`;
- les heures supplémentaires validées dans `heure_supplementaire`.

La période est sélectionnée dans l'onglet **Paie**. Une période possède les
statuts suivants :

| Statut | Signification |
|---|---|
| `OUVERT` | Période prête pour le premier calcul |
| `CALCUL` | Bulletins calculés mais non validés |
| `VALIDE` | Bulletin verrouillé |
| `PAIE` | Paiement enregistré |
| `CLOTURE` | Période historique terminée |
| `ANNULE` | Période annulée |

### Calculer une paie

1. Ouvrir `/rh/modules-avances`.
2. Sélectionner une période, par exemple novembre 2026.
3. Cliquer sur **Calculer les bulletins**.
4. Lire les montants dans le tableau.
5. Cliquer sur **Valider la période** après vérification.
6. Exporter l'état de paie en Excel ou ouvrir un bulletin PDF.

Une période validée n'est plus recalculable. Les bulletins validés sont
immuables. Toute régularisation doit produire un nouvel enregistrement
correctif.

### Relation avec les pointages

La relation actuelle est la suivante :

| Élément | Utilisé dans la paie ? |
|---|---|
| Pointages `pointage` | Pas directement dans `PaieService` |
| Absences `absence` | Oui, pour les heures absentes non justifiées |
| Congés validés `demande_conge` | Pas directement dans le calcul automatique |
| Heures supplémentaires `heure_supplementaire` | Oui, si le statut est `VALIDE` |
| Temps travaillé `temps_travaille` | Pas utilisé directement |
| Retards `retard` | Pas déduits automatiquement |

Le moteur considère les absences de `absence` où `justifiee = 0`. Une absence
justifiée ne réduit pas actuellement le salaire.

Nombre d'heures absentes :

```text
Heures absentes = nombre de jours non justifiés × HEURES_JOUR
```

Dans les paramètres de démonstration :

```text
HEURES_JOUR = 8
```

Exemple :

```text
3 jours d'absence non justifiée
3 × 8 = 24 heures absentes
```

Si le salaire est 1 200 000 Ar et que la période non agricole utilise
173,33 heures :

```text
Taux horaire = 1 200 000 / 173,33
Taux horaire = 6 923,44 Ar/h

Retenue = 24 × 6 923,44
Retenue = 166 162,56 Ar
```

Le pointage reste un module séparé. Pour prendre automatiquement en compte
les absences, les retards ou les heures réellement effectuées, il faut
transmettre vers `absence`, `retard` ou `heure_supplementaire` une ligne
confirmée issue du pointage. Ce lien automatique n'est pas encore intégré
dans tous les écrans.

### Salaire utilisé

Le moteur recherche le dernier `historique_salaire` dont la date d'effet est
antérieure à la fin du mois.

```text
Salaire du mois = dernier historique salarial applicable
                ou salaire du contrat
```

Cela permet une révision salariale en cours de mois. Exemple :

- contrat : 1 000 000 Ar;
- évolution au 15 novembre : 1 100 000 Ar;
- paie de novembre : 1 100 000 Ar.

Une évolution peut être insérée avec :

```sql
INSERT INTO historique_salaire (
    id_employe,
    ancien_salaire,
    nouveau_salaire,
    periodicite_salaire,
    motif,
    id_utilisateur,
    date_effet
) VALUES (
    1,
    1200000,
    1300000,
    'MENSUEL',
    'Revalorisation annuelle',
    2,
    '2026-12-01'
);
```

Cette opération ne doit pas remplacer les anciens bulletins. Elle crée un
nouveau mouvement historique.

### Indemnités et primes

Les éléments mensuels sont enregistrés dans `variable_paie` et liés à une
rubrique.

Rubriques courantes :

| Code | Effet |
|---|---|
| `INDEMNITE` | Gain imposable ajouté au brut |
| `RAPPEL` | Rappel ou ajustement imposable |
| `RETENUE` | Accompte, avance ou retenue |

Créer une indemnité :

```sql
INSERT INTO variable_paie (
    id_employe,
    id_periode,
    id_rubrique,
    nombre,
    montant,
    motif
) VALUES (
    1,
    1,
    10,
    1,
    100000,
    'Prime de performance'
);
```

Dans cet exemple, `100000` signifie 100 000 Ar. Il faut que
`rubrique_paie.id_rubrique = 10` existe et corresponde à un code de gain
approprié.

Exemple avec les rubriques standardisées du moteur :

```sql
INSERT OR IGNORE INTO rubrique_paie (
    code,
    libelle,
    nature,
    imposable
) VALUES (
    'INDEMNITE',
    'Indemnite mensuelle',
    'GAIN',
    1
);

INSERT INTO variable_paie (
    id_employe,
    id_periode,
    id_rubrique,
    nombre,
    montant,
    motif
) VALUES (
    1,
    1,
    (SELECT id_rubrique FROM rubrique_paie WHERE code = 'INDEMNITE'),
    1,
    100000,
    'Transport et logement'
);
```

Montants combinés :

```text
Salaire brut =
  base ajustée
  + INDEMNITE
  + RAPPEL
  + majorations d'heures supplémentaires
```

Une indemnité non imposable peut être conservée pour affichage, mais le moteur
actuel doit la déclarer dans `INDEMNITE` pour l'ajouter au brut. Il faudra
une rubrique dédiée si l'indemnité doit être hors assiette IRSA.

### Heures supplémentaires

Insérer une heure supplémentaire validée :

```sql
INSERT INTO heure_supplementaire (
    id_employe,
    date_travail,
    heures,
    motif,
    statut,
    id_validateur,
    nuit
) VALUES (
    1,
    '2026-11-22',
    4,
    'Livraison urgente',
    'VALIDE',
    2,
    1
);
```

Calcul normalisé :

```text
Taux horaire = salaire / 173,33
Coefficient 30 % = 1,30
Coefficient 50 % = 1,50
Coefficient dimanche = 2,00
Coefficient nuit = 1,50
```

Exemple :

```text
Salaire = 1 200 000 Ar
Taux horaire = 6 923,44 Ar/h
4 heures normales à 30 %
Montant = 4 × 6 923,44 × 1,30
Montant = 36 001,89 Ar
```

Exemple dimanche de nuit :

```text
Coefficient = 1,30 × 2 × 1,50 = 3,90
Montant = 2 × 6 923,44 × 3,90
Montant = 54 002,84 Ar
```

L'application limite les heures supplémentaires à 20 heures par semaine et
applique les premières 12 heures à 30 %, puis les suivantes à 50 %.

### Cotisations

Exemple non agricole :

```text
Salaire brut = 1 400 000 Ar
Plafond CNaPS = 350 000 × 8 = 2 800 000 Ar
Assiette CNaPS = 1 400 000 Ar

CNaPS salarié = 1 400 000 × 1 % = 14 000 Ar
CNaPS patronal = 1 400 000 × 13 % = 182 000 Ar

OSTIE salarié = 1 400 000 × 1 % = 14 000 Ar
OSTIE patronal = 1 400 000 × 5 % = 70 000 Ar
```

CNaPS est plafonnée. OSTIE est calculée sur le brut non plafonné.

Pour un secteur agricole :

```text
CNaPS salarié = brut plafonné × 6 %
CNaPS patronal = brut plafonné × 8 %
```

### IRSA

Le barème est configuré dans `bareme_irsa` :

| Tranche | Taux |
|---|---:|
| 0 à 350 000 Ar | 0 % |
| 350 000 à 400 000 Ar | 5 % |
| 400 000 à 500 000 Ar | 10 % |
| 500 000 à 600 000 Ar | 15 % |
| 600 000 à 4 000 000 Ar | 20 % |
| Au-dessus de 4 000 000 Ar | 25 % |

Exemple :

```text
Brut = 1 400 000
CNaPS salarié = 14 000
OSTIE salarié = 14 000
Net imposable = 1 372 000

350 000 × 0 % = 0
50 000 × 5 % = 2 500
100 000 × 10 % = 10 000
100 000 × 15 % = 15 000
772 000 × 20 % = 154 400

IRSA = 181 900 Ar
```

### Net à payer

```text
Net = brut
    - CNaPS salarié
    - OSTIE salarié
    - IRSA
    - acomptes ou avances
```

Exemple :

```text
Net = 1 400 000 - 14 000 - 14 000 - 181 900
Net = 1 190 100 Ar
```

---

## Bloc 9 — Compétences, objectifs, évaluations et formation

### Référentiel de compétences

Créer une compétence :

```sql
INSERT INTO competence (code, libelle, description)
VALUES ('JAVA', 'Développement Java', 'Programmation orientée objet');
```

Associer une compétence à un poste :

```sql
INSERT INTO poste_competence (
    id_poste,
    id_competence,
    niveau_attendu
) VALUES (
    1,
    1,
    4
);
```

Le niveau attendu est compris entre 1 et 5.

Évaluer un employé :

```sql
INSERT INTO employe_competence (
    id_employe,
    id_competence,
    niveau_acquis,
    date_evaluation,
    id_evaluateur
) VALUES (
    1,
    1,
    3,
    '2026-11-20',
    2
);
```

Calcul automatique de l'écart :

```text
Écart = niveau attendu - niveau acquis
```

Exemple :

```text
Niveau attendu = 4
Niveau acquis = 3
Écart = 1
```

Un écart positif indique un besoin de formation.

Créer un plan de formation depuis l'onglet **Évaluation & formation** ou
directement avec `INSERT INTO formation`. L'ajout d'un participant se fait dans
`formation_participant`.

Après formation, mettre à jour `niveau_avant` et `niveau_apres`, puis créer une
nouvelle évaluation dans `employe_competence`.

---

## Bloc 10 — Carrière et mobilité

Créer une promotion, mutation, affectation ou révision salariale depuis
l'onglet **Carrière**.

Les données sont enregistrées dans `mouvement_carriere`.

Exemple :

```sql
INSERT INTO mouvement_carriere (
    id_employe,
    type_mouvement,
    ancien_poste,
    nouveau_poste,
    ancien_salaire,
    nouveau_salaire,
    id_departement,
    date_effet,
    motif,
    id_auteur
) VALUES (
    1,
    'PROMOTION',
    'Développeur junior',
    'Développeur confirmé',
    1200000,
    1450000,
    1,
    '2026-12-01',
    'Promotion annuelle',
    2
);
```

Types autorisés :

- `PROMOTION`
- `MUTATION`
- `CHANGEMENT_STATUT`
- `REVISION_SALAIRE`
- `AFFECTATION`

Les affectations ont une date de début et une date de fin optionnelle. Deux
affectations actives pour le même salarié doivent être évitées :

```sql
SELECT id_employe
FROM affectation
WHERE date_fin IS NULL
GROUP BY id_employe
HAVING COUNT(*) > 1;
```

Pour une reconstitution historique, utiliser la date d'effet :

```sql
SELECT *
FROM mouvement_carriere
WHERE id_employe = 1
  AND date_effet <= '2026-10-15'
ORDER BY date_effet DESC;
```

Une révision de poste ne suffit pas toujours à modifier le salaire. La nouvelle
rémunération doit être inscrite dans `historique_salaire` pour être prise en
compte dans les calculs futurs.

---

## Bloc 11 — Discipline

La sanction suit obligatoirement ce workflow :

```text
Incident signalé
  -> Demande d'explications
  -> Réponse du salarié
  -> Commission / décision
  -> Notification de sanction
```

1. Signaler l'incident dans l'onglet **Discipline**.
2. Créer la demande d'explications.
3. Enregistrer la réponse du salarié.
4. Décider uniquement après réception de la réponse.
5. Vérifier que l'incident est bien archivé.

Une sanction ne peut pas être enregistrée sans réponse contradictoire.

Incident :

```sql
INSERT INTO incident (
    id_employe,
    date_incident,
    objet,
    description,
    statut,
    id_signaleur
) VALUES (
    1,
    '2026-11-20',
    'Retard répétitif',
    'Plusieurs retards non justifiés',
    'SIGNALE',
    2
);
```

Demande d'explications :

```sql
INSERT INTO demande_explication (
    id_incident,
    date_demande,
    date_limite,
    texte
) VALUES (
    1,
    '2026-11-20',
    '2026-11-25',
    'Veuillez fournir vos explications dans le délai indiqué.'
);
```

Le délai doit être contrôlé par le processus métier. Une réponse reçue après
`date_limite` doit être traitée avec une justification légale.

Sanction autorisée après réponse :

```text
AVERTISSEMENT
BLAME
MISE_A_PIED
LICENCEMENT
AUTRE
```

Toutes les consultations sensibles doivent être journalisées dans
`journal_acces` ou `audit_rh`.

---

## Bloc 12 — Rupture et offboarding

Depuis l'onglet **Ruptures**, sélectionner :

- le salarié;
- le motif;
- la date de notification;
- la date effective de sortie;
- les détails.

La création de rupture génère automatiquement :

- une ligne `rupture_contrat`;
- une checklist d'offboarding;
- un solde de tout compte initial;
- les attestations prévues dans l'application.

Motifs autorisés :

- `DEMISSION`
- `LICENCIEMENT_ECONOMIQUE`
- `LICENCIEMENT_FAUTE`
- `FIN_CDD`
- `RETRAITE`
- `RUPTURE_CONVENTIONNELLE`
- `AUTRE`

### Solde de tout compte

Formule générale :

```text
STC =
  salaire proratisé
  + indemnité compensatrice des congés non pris
  + indemnité de rupture
  - avances
  - retenues
```

Dans l'implémentation actuelle :

```text
dernier_salaire = salaire du contrat
salaire_proratise = salaire du contrat
indemnite_conges = 0
indemnite_rupture = 0
avances = 0
retenues = 0
montant_net = salaire du contrat
```

Le STC actuel doit donc être considéré comme une **initialisation**, pas comme
un calcul légal complet.

Avant finalisation, saisir ou recalculer les éléments depuis les données
administratives :

```sql
INSERT OR REPLACE INTO solde_tout_compte (
    id_rupture,
    dernier_salaire,
    salaire_proratise,
    indemnite_conges,
    indemnite_rupture,
    avances,
    retenues,
    montant_net,
    statut
) VALUES (
    1,
    1600000,
    800000,
    250000,
    200000,
    50000,
    10000,
    1190000,
    'BROUILLON'
);
```

Exemple de calcul :

```text
Dernier salaire = 1 600 000
Salaire proratisé = 800 000
Congés non pris = 250 000
Indemnité de rupture = 200 000
Avances = 50 000
Retenues = 10 000

STC = 800 000 + 250 000 + 200 000 - 50 000 - 10 000
STC = 1 190 000 Ar
```

### Congés non pris

Lire le solde annuel :

```sql
SELECT acquis, pris, ajuste
FROM solde_conge
WHERE id_employe = 1
  AND id_type_conge =
      (SELECT id_type_conge FROM type_conge WHERE code = 'ANNUEL')
  AND annee = 2026;
```

Le nombre restant peut être utilisé pour l'indemnité compensatrice :

```text
Jours restants = acquis - pris + ajuste
Indemnité = taux journalier × jours restants
```

Le calcul automatique complet des congés doit aussi exclure les périodes déjà
indemnisées et vérifier les règles légales applicables.

### Relation STC et pointage

Le STC n'est pas encore relié automatiquement aux pointages.

Les données pointage peuvent être utilisées pour :

- vérifier les jours réellement travaillés;
- calculer le prorata du dernier mois;
- déterminer les absences du mois de sortie;
- calculer les heures supplémentaires dues;
- justifier les retenues ou avances.

Mais l'application ne calcule pas encore automatiquement :

```text
STC à partir du pointage
```

Elle utilise actuellement surtout les données `contrat`,
`historique_salaire`, `solde_tout_compte` et les valeurs saisies
manuellement.

### Checklist d'offboarding

Éléments standardisés :

- STC;
- dernier bulletin;
- certificat de travail;
- attestations sociales;
- restitution du matériel;
- coupure des accès;
- archivage du dossier.

Modifier un élément :

```sql
UPDATE checklist_offboarding
SET valeur_statut = 'TERMINE',
    date_realisation = CURRENT_TIMESTAMP,
    commentaire = 'Matériel restitué'
WHERE id_rupture = 1
  AND element = 'Restitution matériel';
```

---

## Procédure complète recommandée

### Pour une paie mensuelle

1. Vérifier les contrats et salaires.
2. Vérifier les pointages.
3. Mettre les absences non justifiées dans `absence`.
4. Valider les heures supplémentaires.
5. Saisir les indemnités, rappels et retenues dans `variable_paie`.
6. Vérifier les paramètres CNaPS, OSTIE et IRSA.
7. Calculer les bulletins.
8. Vérifier les montants.
9. Valider la période.
10. Exporter l'état Excel et les PDF individuels.

### Pour une rupture

1. Vérifier le motif légal.
2. Vérifier les dates de préavis.
3. Mettre à jour le statut du salarié.
4. Créer la rupture.
5. Contrôler le dernier salaire.
6. Calculer le prorata.
7. Lire le solde des congés annuels.
8. Calculer les indemnités.
9. Déduire avances et retenues.
10. Valider le STC.
11. Terminer la checklist.
12. Désactiver les accès à la date de sortie.
13. Archiver les attestations et le dossier.

---

## Points encore à automatiser

- liaison complète pointage -> absence;
- liaison pointage -> retenue de retard;
- liaison pointage -> temps réellement travaillé;
- création automatique du STC;
- prorata automatique du dernier mois;
- indemnité automatique des congés non pris;
- rattachement automatique des primes depuis toutes les rubriques;
- génération automatique des certificats PDF;
- coupure automatique des accès à la date de sortie;
- contrôle des chevauchements d'affectations;
- blocage légal des sanctions sans procédure contradictoire complète;
- mise à jour automatique du salaire lors d'une promotion.
