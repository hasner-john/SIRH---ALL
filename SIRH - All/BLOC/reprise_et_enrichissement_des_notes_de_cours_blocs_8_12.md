# Synthese et Structure des Blocs RH 8 à 12

---

## Bloc 8 — Paie et Cotisations

### Description Générale
Le bloc Paie transforme les données de présence, de contrat et d'activités en rémunération nette, tout en assurant le calcul et le versement des cotisations sociales et impôts légaux.

### Entrées du Système
- **Contrat & Statut :** Salaire de base, catégorie, secteur (Agricole / Non Agricole).
- **Pointage & Absences :** Heures travaillées, retards, absences déductibles.
- **Événements du mois :** Primes, indemnités, rajustements, heures supplémentaires et majorations.

### Moteur de Calcul
1. **Dépouillement des heures et absences :** Application des taux horaires et coefficients multiplicatifs.
2. **Calcul du Brut :** Somme du salaire de base ajusté, des primes et des heures majorées.
3. **Cotisations Sociales :** Déduction CNaPS (Part Salariale) et OSTIE (Part Salariale).
4. **Calcul de l'IRSA :** Imposition progressive selon le barème officiel par tranches.
5. **Nets & Retenues :** Déduction des acomptes/avances pour obtenir le Net à payer final.

### Objets Métier Principaux
- `PeriodePaie`
- `RubriquePaie`
- `VariablePaie`
- `BulletinPaie`
- `LigneBulletin`

---

## Bloc 9 — Évaluation, Compétences et Formation

### Description Générale
Permet d'aligner les compétences du personnel sur les exigences stratégiques de l'entreprise au moyen d'évaluations régulières et de plans de formation ciblés.

### Processus & Workflow
1. **Définition du Référentiel :** Attributions des objectifs et des compétences requises par poste.
2. **Évaluation Périodique :** Mesure des performances et des compétences acquises par l'employé.
3. **Analyse des Écarts (Gap Analysis) :** Comparaison automatique entre compétences requises et compétences observées.
4. **Plan de Formation :** Déclenchement automatique des besoins de formation pour combler les écarts détectés.
5. **Évaluation Post-Formation :** Mesure de l'impact et de la progression suite aux actions de formation.

### Objets Métier Principaux
- `Competence`
- `EmployeCompetence`
- `Objectif`
- `Evaluation`
- `Formation`

---

## Bloc 10 — Carrière et Mobilité

### Description Générale
Assure la traçabilité complète de l'évolution professionnelle de chaque salarié au sein de l'organisation.

### Points Clés & Règles
- **Mobilité Interne :** Prise en charge des promotions, mutations horizontales, changements de classification ou de rémunération.
- **Historisation Temporelle :** La donnée RH est temporelle par nature. Chaque changement conserve la trace de son historique (Ancien poste, Nouveau poste, Date d'effet, Motif).
- **Reconstitution de Carrière :** Possibilité de restituer le profil exact et l'historique d'un salarié à n'importe quelle date clé dans le passé.

### Objets Métier Principaux
- `HistoriqueAffectation`
- `Promotion`
- `Mutation`
- `HistoriqueSalaire`

---

## Bloc 11 — Discipline

### Description Générale
Cadre la gestion des écarts de conduite, des sanctions et du respect du règlement intérieur, dans le respect des contraintes légales.

### Points Clés & Règles
- **Workflow Linéaire Transparent :**
  $$\text{Signalement d'incident} \rightarrow \text{Demande d'explications} \rightarrow \text{Réponse de l'employé} \rightarrow \text{Décision administrative} \rightarrow \text{Sanction}$$
- **Garantie des Droits :** Interdiction d'appliquer une sanction sans une procédure contradictoire formelle.
- **Confidentialité et Archivage :** Les dossiers disciplinaires sont à accès restreint et soumis à un délai de prescription et de conservation légal.

### Objets Métier Principaux
- `Incident`
- `DemandeExplication`
- `ReponseExplication`
- `Sanction`

---

## Bloc 12 — Rupture et Offboarding

### Description Générale
Gère le départ d'un collaborateur, de l'enregistrement de la fin de contrat jusqu'à l'archivage complet de son dossier.

### Étapes Clés
1. **Enregistrement du Motif :** Démission, licenciement, fin de CDD, départ à la retraite, rupture conventionnelle.
2. **Gestion du Préavis & Dates :** Calcul de la date officielle de sortie et suivi de la période de préavis.
3. **Calcul du Solde de Tout Compte (STC) :** Récapitulatif du salaire restant du, des congés non pris et des indemnités de départ.
4. **Restitution du Matériel :** Suivi du retour des équipements (ordinateurs, véhicules, badges, clés).
5. **Coupure des Accès & Clôture :** Désactivation programmée des comptes applicatifs et archivage sécurisé du dossier.

### Objets Métier Principaux
- `RuptureContrat`
- `SoldeToutCompte`
- `ChecklistOffboarding`
- `AttestationTravail`