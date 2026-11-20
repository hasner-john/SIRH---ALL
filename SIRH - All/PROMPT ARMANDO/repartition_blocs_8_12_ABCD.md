# Répartition du travail — Blocs RH 8 à 12 (Danie, John, Rindra, Tsiky)

Ce plan répartit le fichier « reste à faire » entre quatre personnes (Danie, John, Rindra, Tsiky), en intégrant les règles de gestion RG-TRANS du cahier des charges. Chacun a environ 22 points de charge (estimation relative).

| Personne | Périmètre | Charge |
|---|---|---|
| **Tsikyanie** | Paie complète + base SQL + notifications + PDF du STC | ≈ 23 |
| **John** | Évaluation/Formation + Embauche directe + Contrat PDF + Paramètres RH | ≈ 22 |
| **Rindra** | Carrière + Rupture (service, cycle de vie, STC) + menu RH | ≈ 22 |
| **Tsiky** | Discipline + Espace employé + Admin/Transitions/Routes + Attestations | ≈ 22 |

---

# 0. À faire par TOUT LE MONDE

- [ ] **Base unique** : appliquer le SQL dans l'ordre 1.Danie (4 tables remplacées) → 1.John (`migration_blocs_8_12.sql`) → 1.Rindra (seed), puis recréer la base. Personne ne modifie le schéma dans son coin, tout passe par Danie.
- [ ] **Pas de Model CodeIgniter** : `\Config\Database::connect()` + Query Builder, services dans `app/Services/`.
- [ ] **Transactions** : toute opération sur plusieurs tables passe par `transBegin/transCommit` avec rollback complet en cas d'échec.
- [ ] **Changements d'état** : tout bouton de statut passe par `Transitions`. Si la transition est refusée, afficher un message.
- [ ] **Notifications** : toujours via `NotificationService::envoyer()`.
- [ ] **Nouvelle page** : ajouter son lien dans la grille admin (Tsiky) et dans le menu `rh/layout` (Rindra). Pas de modification directe de `Routes.php` : envoyer ses routes à Tsiky.
- [ ] **Test** : chaque fonctionnalité est testée avec `dev/login-as` du bon rôle, et un rôle non autorisé est refusé.
- [ ] **Revue croisée avant fusion** : Danie ↔ Rindra (paie/rupture) et John ↔ Tsiky.

**Règles de gestion à respecter par tous :**

| RG | Ce que ça impose concrètement |
|---|---|
| RG-TRANS-01 | Chaque écriture ou modification sensible laisse une ligne `audit_rh` : user, date UTC, action, `objet_id`, ancienne/nouvelle valeur, motif. |
| RG-TRANS-02 | Rien d'historique n'est écrasé. Une correction est un nouvel enregistrement lié (nouvelle évaluation, `RAPPEL` de paie, nouveau contrat). |
| RG-TRANS-03 | Toute donnée contractuelle ou salariale a une `date_effet`, et on doit pouvoir reconstituer la situation à une date passée. |
| RG-TRANS-04 | Accès par rôle, avec `journal_acces` pour les bulletins et les dossiers disciplinaires. |
| RG-TRANS-05 | Aucun taux, plafond ou durée en dur dans le code : tout vient de `parametre_rh` ou `ParametrePaie`. |

---

# Jalons et dépendances globales

| Jalon | Livrables | Débloque |
|---|---|---|
| **J0** (à finir en premier) | A0, A1, B0, C0, D0 | tout le monde |
| **J1** | A2 (`assurer()`), B7 (contrat PDF), C5 (`RuptureService::creer`), D2 (espace employé) | B8, C1, D6 |
| **J2** | A6 (calcul paie), B2 (évaluations), C1 (carrière), D4 (workflow) | C4, A7, C7 |
| **J3** | C4, C7 → A8, D6, D7, A7 | intégration finale |

---

# Danie — Paie + socle SQL + notifications

**RG clés :** 05 (SME, CNaPS/OSTIE, tranches IRSA, `PLAFOND_HS` en base), 02 (un bulletin `VALIDE` est immuable, correction par `RAPPEL`), 04 (log d'accès sur les bulletins). Les 24 colonnes de l'état de paie du cahier des charges sont conservées.

- [ ] **Danie0 — SQL commun** *(J0, bloque tous)* : créer `migration_blocs_8_12.sql`, remplacer les 4 tables dans `base_recrutement.sql`, corriger le seed (contrat 7 en doublon, variables HS) et republier la base.
- [ ] **Danie1 — `NotificationService::envoyer()`** *(J0, bloque tous)*.
- [ ] **Danie2 — `PeriodePaieService::assurer()` + page `rh/paie/periodes`** (3.2) : cycle OUVERT→CALCUL→VALIDE→PAIE→CLOTURE, avertissement si période verrouillée.
  - ⬅ dépend de A0 et D0 (Transitions).
  - ➡ B8 (embauche) et C7 attendent `assurer()`.
- [ ] **Danie3 — Variables de paie** (3.3) : upsert, catégories INDEMNITE/PRIME/RAPPEL/AVANCE, jamais HS. ⬅ A0, A2.
- [ ] **Danie4 — `HeureSuppService` + page HS** (3.4) : découpe jour/nuit/dimanche, plafond hebdomadaire de 20 h, aperçu en direct. ⬅ A0, B0 (`ParametreRh` pour `NUIT_*`).
- [ ] **Danie5 — Retards** (3.5) : saisie manuelle, justification/validation, auto-détection au pointage dans `TempsTravailController`. ⬅ A0, B0, A1.
- [ ] **Danie6 — Refonte de `PaieService`** (3.6 a→f) : prorata, chaîne de calcul, mise à pied, `inclus_stc`, prise en compte des `PARTI`, retards déduits du brut. ⬅ A3, A4, A5.
  - Lit `sanction` (Tsiky) pour la mise à pied et `rupture_contrat` (Rindra) pour la date de sortie. Tester avec des lignes insérées dans le seed.
- [ ] **Danie7 — Documents** (3.7) : Excel (colonne 11 = somme des retenues, plus « dont indemnités non imposables » et « Payé via STC »), PDF bulletin (lignes 1011, 1012, 1013, 2900, `jours_travailles/30`), `log d'accès`. ⬅ A6.
- [ ] **Danie8 — PDF du STC** (7.4, Dompdf, `enLettres`, `pdf_donnees`). ⬅ C7.
- [ ] **Bugs :** 2 (cellule `<th>` dans `modules_avances`), 3, 4, 10.

---

# John — Évaluation/Formation + Embauche + Paramètres

**RG clés :** 02 (évaluation `VALIDE` non modifiable, contrat `SIGNE` servi tel quel), 05 (seuil de formation en base), 01 (audit).

- [ ] **John0 — `ParametreRh::get()` + page `rh/parametres`** (2.6) *(J0)* : onglets par groupe, audit à chaque modification, seuil modifiable directement sur les pages Performance et Propositions. Bloque A4, A5 et C4.
- [ ] **John1 — Référentiel** (4.1) : CRUD des compétences et matrice poste × compétence (niveau 1 à 5). ⬅ A0.
- [ ] **John2 — `EvaluationService` + `rh/performance`** (4.2) : saisie, validation, `ecart_competence`, propositions de formation, détail avec comparaison ↑↓=. ⬅ B1, B0, A1.
  - ➡ C4 utilise les écarts et notes validés.
- [ ] **John3 — Recalcul des propositions** (4.3). ⬅ B2.
- [ ] **John4 — `rh/formations/propositions`** (4.4) : assigner (avec notification) ou refuser (commentaire obligatoire). ⬅ B2.
- [ ] **John5 — `rh/formations/assigner`** (4.5) : multi-employés, doublons listés dans le message de retour. ⬅ A1.
- [ ] **John6 — Apport de la formation** (4.6) : `niveau_apres`, évaluation POST_FORMATION, page `apport` lue depuis `v_apport_formation`. ⬅ B2.
- [ ] **John7 — `ContratPdfService`** (7.5) *(J1)* : génération, stockage dans `contrat.pdf_*`, un contrat `SIGNE` n'est jamais régénéré, route `rh/contrats/(:num)/pdf`, `telechargerPdf` du candidat. ⬅ A0.
  - ➡ C1 (nouveau contrat) et B8.
- [ ] **John8 — `EmbaucheController`** (3.1) : 3 étapes, transaction unique de 10 sous-étapes, matricule et référence automatiques. ⬅ A0, A1, A2 (`assurer()`), B7.

---

# Rindra — Carrière + Rupture

**RG clés :** 03 (reconstitution à une date), Bloc 10 (aucun chevauchement d'affectations actives), 02 (STC `VALIDE` immuable), formule STC du cahier des charges, 04.

- [ ] **Rindra0 — Menu `rh/layout`** *(J0)* : ajouter toutes les entrées des nouveaux blocs en une seule fois, pour éviter les conflits de fusion.
- [ ] **Rindra1 — `CarriereService::appliquer()` + `appliquerEchus()`** (5.1–5.3) : cas Danie (salaire) et cas John (nouveau contrat, ancien `REMPLACE`), affectations sans chevauchement, `audit_rh` avec `objet_id`. ⬅ A0, D0, B7.
- [ ] **Rindra2 — Formulaire de mouvement** (5.2) : anciennes valeurs lues en base, champs affichés selon le type, `PLANIFIE` si la date est future. ⬅ C1.
- [ ] **Rindra3 — Parcours** `rh/employes/(:num)/parcours` (5.5) : « Situation au JJ/MM/AAAA » et frise. ⬅ C1.
- [ ] **Rindra4 — `PropositionCarriereService` + page** (5.4) : règles R1 à R4, garde-fous, justification chiffrée, visible RH uniquement. ⬅ B2 (évaluations), A1.
  - Lit `sanction` (Tsiky) pour les garde-fous : tester avec le seed.
- [ ] **Rindra5 — `RuptureService::creer()`** (7.1) *(J1)* : date de sortie calculée, refus si rupture existante, checklist standard (7 libellés), STC brouillon, attestations vides. ⬅ A0.
  - ➡ D6 : le licenciement par discipline appelle cette méthode.
- [ ] **Rindra6 — Cycle de vie + `appliquerSorties()` + page détail** (7.2) : `PARTI`, contrat `TERMINE`, coupure des accès, automatismes de checklist. ⬅ C5, D0.
- [ ] **Rindra7 — `StcService::calculer()`** (7.4) : formule du cahier des charges (salaire proratisé + congés + indemnité de rupture − avances/retenues), recalcul tant que `BROUILLON`. ⬅ C5. Utilise le net du dernier bulletin de A6, avec repli `base × jours / 30` pour ne pas être bloqué.
  - ➡ A8.
- [ ] **Bugs :** 5 (`creationCarriere`), 6 (`creerRupture`).

---

# Tsiky — Discipline + Espace employé + Admin

**RG clés :** Bloc 11 (aucune sanction sans phase contradictoire traçable, délais contrôlés), 04 (accès restreint + `journal_acces` `DOSSIER_DISCIPLINAIRE`), 01.

- [ ] **Tsiky0 — Socle sécurité** *(J0, bloque tous)* :
  - `RoleFilter` : l'ADMINISTRATEUR passe partout.
  - `Routes.php` : supprimer le groupe `direction` dupliqué, poser le squelette complet du §8.
  - `Transitions` : tableau complet du 2.3.
  - `dev/login-as` : ajouter `EMPLOYE` et corriger les emails.
  - Redirection `/employe` dans `AuthController`.
- [ ] **Tsiky1 — Page admin** (2.2) : grille de cartes par rôle pilotée par le tableau `$liens`. ⬅ D0.
- [ ] **Tsiky2 — Espace employé** (2.5) *(J1)* : accueil, notifications, formations ; bulletins en option, avec `journal_acces`. ⬅ A1.
  - Lit `formation_participant` (B5) : tester avec le seed.
  - ⬅ A7 pour les bulletins.
- [ ] **Tsiky3 — Pages RH discipline** (6.2) : incidents, dossier, sanctions, réponses, filtres, `journal_acces`, correction du bug `incidents[0]`. ⬅ A0, D0.
- [ ] **Tsiky4 — Workflow** (6.3) : demande d'explications avec notification, réponse de l'employé (texte + pièce jointe ≤ 2 Mo, `en_retard` si après `date_limite`), saisie papier par la RH, commission. ⬅ D2, A1.
- [ ] **Tsiky5 — Décision** (6.4) : classement sans suite ou sanction, types selon `gravite_sanction_autorisee`, alerte récidive, `nb_jours ≥ 1` pour la mise à pied, champs catégorie et gravité au signalement. ⬅ D4.
- [ ] **Tsiky6 — Effets des sanctions** (6.5) : mise à pied, **LICENCIEMENT → `RuptureService::creer()`**, contestation par l'employé, statut de la sanction. ⬅ D5, C5.
  - ➡ A6 lit les sanctions de type mise à pied.
- [ ] **Tsiky7 — `AttestationService`** (certificat de travail, CNaPS, OSTIE, Dompdf) et route associée. ⬅ C5.
- [ ] **Bugs :** 1 (route dupliquée), 7 (`sanctionner`), 8 (`repondreExplication`), 9 (`dev/login-as`), 2 (formulaires `incidents[0]`).

---

# Matrice de dépendances entre personnes

| Qui attend | Danie | John | Rindra | Tsiky |
|---|---|---|---|---|
| **Tsikyanie** | | `ParametreRh` (B0) | rupture/date de sortie, STC (C7) | `Transitions` (D0), sanctions de mise à pied (D6) |
| **John** | SQL, notifications, `assurer()` | | | `Transitions` (D0) |
| **Rindra** | SQL, net du dernier bulletin (A6) | évaluations (B2), contrat PDF (B7) | | `Transitions` (D0), sanctions (Tsiky) |
| **Tsiky** | SQL, notifications, bulletins (A7) | | `RuptureService::creer` (C5) | |

**Chemin critique :** A0 → A2 → B8, et A0 → C5 → D6. À livrer en priorité.

---
