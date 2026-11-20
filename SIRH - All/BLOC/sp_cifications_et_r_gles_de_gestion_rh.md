# Cahier des Charges & Règles de Gestion RH — Blocs 8 à 12

**Version :** 2.0 (Corrigée et Validée)  
**Pays / Contexte :** Madagascar  

---

## 1. Principes Transversaux (System-Wide)

### RG-TRANS-01 — Traçabilité et Piste d'Audit
Toute opération d'écriture, suppression ou modification sensible doit conserver : 
- Identifiant de l'auteur (User ID)
- Horodatage serveur (ISO-8601 UTC)
- Type d'action (CREATE, UPDATE, DELETE)
- Objet ciblé et identifiant unique
- Ancienne valeur / Nouvelle valeur
- Motif justificatif ou pièce jointe justificative

### RG-TRANS-02 — Immuabilité et Historisation
Les données historiques ayant servi au calcul d'une paie, d'une cotisation, d'une affectation ou d'une évaluation ne doivent jamais être écrasées ou supprimées. Une régularisation fait obligatoirement l'objet d'un nouvel enregistrement correctif lié à l'enregistrement d'origine.

### RG-TRANS-03 — Temporalité et Dates d'Effet
Toute donnée contractuelle ou salariale comporte une date d'effet et une date de fin de validité. Le système doit permettre la reconstitution exacte de la situation administrative et financière d'un salarié à n'importe quelle date passée ou présente.

### RG-TRANS-04 — Droits d'Accès et Confidentialité (RBAC)
L'accès aux modules Paie, Carrière et Discipline est strictement restreint selon le rôle attribué à l'utilisateur. Toute consultation ou modification d'un dossier disciplinaire ou d'un bulletin de paie doit faire l'objet d'un log d'accès sécurisé.

### RG-TRANS-05 — Centralisation des Paramètres
Les taux de cotisations, plafonds légaux, tranches d'IRSA, SME de référence et diviseurs d'heures doivent être stockés en base de données avec historisation temporelle et ne jamais être codés en dur dans le code source de l'application.

---

## 2. Bloc 8 — Paie et Cotisations Sociales

### 2.1. Objets Métier Principal
- `PeriodePaie` : Période mensuelle (ex: Novembre 2026).
- `RubriquePaie` : Éléments de gain ou de retenue (Salaire de base, Primes, Cotisations, etc.).
- `VariablePaie` : Saisies mensuelles spécifiques (Heures supp., absences, primes exceptionnelles).
- `BulletinPaie` : Document individuel récapitulatif.
- `LigneBulletin` : Détail ligne par ligne des calculs du bulletin.
- `Pointage` / `Absence` : Heures travaillées, absences déductibles ou justifiées.
- `ParametrePaie` : SME, barèmes IRSA, taux CNaPS et OSTIE.

---

### 2.2. Temps de Travail et Calculs de Base

#### Taux de Référence
* **Taux Journalier :**
  $$\text{Taux Journalier} = \frac{\text{Salaire de Base}}{30}$$

* **Taux Horaire Non Agricole (base 173,33 h/mois, 40 h/semaine) :**
  $$\text{Taux Horaire} = \frac{\text{Salaire de Base}}{173,33}$$

* **Taux Horaire Agricole (base 200 h/mois, 48 h/semaine) :**
  $$\text{Taux Horaire} = \frac{\text{Salaire de Base}}{200}$$

#### Deductions pour Absences (RG-PAIE-04)
$$\text{Retenue Absence} = \text{Nombre d'heures d'absence} \times \text{Taux Horaire}$$
$$\text{Salaire de Base du Mois} = \text{Salaire de Base Contractuel} - \text{Retenue Absence}$$

#### Majoration des Heures Supplémentaires & Particulières
1. **Heures Supplémentaires Hebdomadaires (Secteur Non Agricole) :**
   - 12 premières heures (41h à 52h) : Majoration de **30 %** ($\text{Coeff} = 1,30$).
   - 8 heures suivantes (53h à 60h) : Majoration de **50 %** ($\text{Coeff} = 1,50$).
   - Plafond hebdomadaire autorisé : 20 heures supplémentaires maximum.
2. **Travail du Dimanche :** Majoration de **100 %** ($\text{Coeff} = 2,00$).
3. **Travail de Nuit (22h à 4h) :** Majoration de **50 %** ($\text{Coeff} = 1,50$).

#### Cumul Multiplicatif des Majorations
En cas de superposition (ex. Heure supplémentaire à 30 % effectuée de nuit le dimanche) :
$$\text{Taux Majomé} = \text{Taux Horaire} \times 1,30 \times 2,00 \times 1,50 = \text{Taux Horaire} \times 3,90$$

---

### 2.3. Cotisations Sociales et IRSA

#### Salaire Brut
$$\text{Salaire Brut} = \text{Salaire de Base Ajusté} + \text{Primes & Indemnités Imposables} + \text{Majorations (HS/Nuit/Dimanche)} + \text{Rappels}$$

#### CNaPS (Caisse Nationale de Prévoyance Sociale)
- **SME de référence (Paramétrable) :** 350 000 Ar
- **Plafond d'assiette CNaPS ($8 \times \text{SME}$) :** $2\ 800\ 000\text{ Ar}$
- **Assiette CNaPS :** $\min(\text{Salaire Brut}, 2\ 800\ 000\text{ Ar})$
- **Taux Non Agricole :** Part Salariale = **1 %** | Part Patronale = **13 %**
- **Taux Agricole :** Part Salariale = **6 %** | Part Patronale = **8 %**

#### OSTIE / Organismes Sanitaires Interentreprises
- **Assiette OSTIE :** **Salaire Brut (non plafonné)**
- **Part Salariale :** **1 %**
- **Part Patronale :** **5 %**

#### IRSA (Impôt sur le Revenu Synthétique et Assimilés)
- **Assiette Imposable :**
  $$\text{Revenu Imposable} = \text{Salaire Brut} - (\text{CNaPS Salariale} + \text{OSTIE Salariale})$$

- **Barème Progressif par Tranches :**

| Tranche de Revenu Imposable (Ar) | Taux d'Imposition |
|---|---:|
| **De 0 à 350 000 Ar** | 0 % |
| **De 350 001 à 400 000 Ar** | 5 % |
| **De 400 001 à 500 000 Ar** | 10 % |
| **De 500 001 à 600 000 Ar** | 15 % |
| **De 600 001 à 4 000 000 Ar** | 20 % |
| **Plus de 4 000 000 Ar** | 25 % |

$$\text{Total IRSA} = \sum \text{Montants calculés par tranche}$$

#### Salaire Net à Payer
$$\text{Salaire Net} = \text{Salaire Brut} - (\text{CNaPS Salarié} + \text{OSTIE Salarié} + \text{Total IRSA}) - \text{Autres Retenues (Acomptes/Avances)}$$

---

## 3. Détails des Livrables de Paie

### 3.1. Structure du Fichier Excel — "État de Paie"
L'État de paie récapitule l'ensemble du personnel pour un mois donné sous forme de tableau interactif.

#### Colonnes Obligatoires de l'État de Paie (dans l'ordre) :
1. **DATE** (ex: 13-nov-26)
2. **NBRE** (Compteur / N° de ligne)
3. **N° MATR** (Matricule interne)
4. **N° CNaPS** (Identifiant CNaPS salarié)
5. **NOM et Prénoms**
6. **Date d'embauche**
7. **ABSENCE du mois** (Durée/Heures/Jours)
8. **CAT** (Catégorie professionnelle : 1A, 4A, 5B, HC...)
9. **Fonction**
10. **SALAIRE DE BASE** (Contractuel)
11. **RETENUES SUR ABSENCE**
12. **SALAIRE DE BASE DU MOIS** (Ajusté)
13. **INDEMNITÉS** (Logement, transport, representation...)
14. **RAPPEL AUTRES**
15. **HR SUP / MAJORATIONS** (Montant total des heures majorées)
16. **TOTAL SALAIRE BRUT**
17. **CNaPS SALARIAL (1%)**
18. **CNaPS PATRONAL (13%)**
19. **OSTIE SALARIAL (1%)**
20. **OSTIE PATRONAL (5%)**
21. **NET IMPOSABLE** (Assiette IRSA)
22. **IRSA** (Montant total retenu)
23. **TOTAL RETENUES**
24. **NET À PAYER**

*Note d'implémentation :* Le bas de tableau doit comporter une ligne **TOTAL GÉNÉRAL** exécutant les sommes automatiques `SUM()` sur toutes les colonnes financières.

---

### 3.2. Structure du PDF — "Fiche de Paie (Bulletin)"
Le bulletin de paie généré en PDF individuel est découpé en 4 blocs distincts :

#### 1. En-tête Entreprise & Salarié
- Raison sociale, adresse, N° STAT, N° CIF.
- Période de paie (Mois / Année).
- Matricule, Nom & Prénoms, N° CNaPS, Date d'embauche, Ancienneté.
- Catégorie socio-professionnelle, Fonction, Régime (Agricole / Non Agricole).
- Taux de référence : Salaire de base, Taux journalier, Taux horaire.

#### 2. Corps du Bulletin (Tableau des Lignes de Paie)
| Code | Désignation | Base / Nombre | Taux / Coefficient | Gains (Ar) | Retenues (Ar) |
|---|---|---|---|---|---|
| 1000 | Salaire de base du mois | 173,33 h | Taux horaire | XXX XXX,XX | |
| 1010 | Retenue pour absence | X heures | Taux horaire | | XXX,XX |
| 2000 | Prime d'ancienneté | Base | % | XXX,XX | |
| 3010 | HS Majoration 30% | Nbr heures | Taux * 1.30 | XXX,XX | |
| 3020 | HS Majoration 50% | Nbr heures | Taux * 1.50 | XXX,XX | |
| 3030 | Majorations Dimanche/Nuit | Nbr heures | Taux * Coeff | XXX,XX | |
| **BRUT**| **TOTAL SALAIRE BRUT** | | | **XXX XXX,XX**| |
| 4010 | CNaPS Salariale | Assiette CNaPS | 1,00 % | | XX XXX,XX |
| 4020 | OSTIE Salariale | Brut total | 1,00 % | | XX XXX,XX |
| 5000 | IRSA (Calcul par tranches) | Assiette IRSA | Barème | | XXX XXX,XX |

#### 3. Récapitulatif Fiscale et Cotisations Patronales
- Detail des tranches IRSA appliquées.
- Charges patronales : CNaPS (13% / 8%), OSTIE (5%).
- Cumuls annuels : Brut imposable cumulé, IRSA cumulé, Congés payés (Acquis / Pris / Solde).

#### 4. Bas de Page et Validation
- **Net à Payer en Ariary** (En chiffres et en lettres).
- Mode de règlement (Virement bancaire / Espèces / Chèque).
- Date de paiement et mention de réserve légale.

---

## 4. Spécifications des Blocs 9 à 12

### Bloc 9 — Évaluation, Compétences et Formation
- **Référentiel de Compétences :** Association des compétences requises avec niveau attendu par poste.
- **Évaluation :** Mesure du niveau acquis ($N_{\text{acquis}}$).
- **Détection des Écarts :** 
  $$\text{Écart} = N_{\text{attendu}} - N_{\text{acquis}}$$
  Un écart positif déclenche automatiquement une suggestion dans le plan de formation.
- **Suivi Post-Formation :** Réévaluation pour valider le gain de compétences.

---

### Bloc 10 — Carrière et Mobilité
- **Historique Continu :** Registre exhaustif des promotions, mutations, révisions salariales et changements de statut.
- **Temporalité Strict :** Chaque mouvement possède une date d'effet. Les requêtes de reconstitution de carrière doivent restituer l'exact état du salarié à n'importe quelle date donnée.
- **Contrôle d'Integrité :** Interdiction stricte de faire se chevaucher deux affectations actives pour un même salarié.

---

### Bloc 11 — Discipline
- **Workflow Obligatoire :**
  $$\text{Incident Signalé} \longrightarrow \text{Demande d'Explications} \longrightarrow \text{Réponse Salarié} \longrightarrow \text{Commission / Décision} \longrightarrow \text{Notification Sanction}$$
- **Règles d'Or :**
  - Aucune sanction sans phase contradictoire traçable.
  - Délais légaux de réponse strictement contrôlés par l'application.
  - Accès restreint au dossier disciplinaire avec journalisation d'audit systématique.

---

### Bloc 12 — Rupture du Contrat et Offboarding
- **Motifs de Départ :** Démission, Licenciement (économique/faute), Fin de CDD, Retraite, Rupture conventionnelle.
- **Solde de Tout Compte (STC) :**
  $$\text{STC} = \text{Dernier Salaire Proratisé} + \text{Indemnité Compensatrice de Congés non Pris} + \text{Indemnité de Rupture} - \text{Avances & Retenues}$$
- **Checklist d'Offboarding :**
  1. Génération et validation du STC et dernier bulletin de paie.
  2. Édition automatique du Certificat de Travail et des attestations sociales.
  3. Suivi de restitution du matériel (badge, ordinateur, voiture).
  4. Coupure automatique des accès système et répertoires réseau à la date de sortie effective.
  5. Archiving du dossier administratif RH.