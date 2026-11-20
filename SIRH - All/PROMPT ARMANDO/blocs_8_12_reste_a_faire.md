# Blocs 8 à 12 — Tout ce qui reste à faire (SQL + code + règles)

> Document de travail commun. **Règle n°1 : tout le monde exécute le même SQL (partie 1) avant de coder.**
> **Règle n°2 : pas de Model CodeIgniter** pour ces blocs. On reste comme l'existant : `\Config\Database::connect()` + Query Builder dans les contrôleurs et dans `app/Services/`.

---

## 0. Sommaire

1. SQL commun (à passer en premier)
2. Transverse : admin, rôles, statuts, notifications, espace employé, paramètres RH
3. Bloc Paie
4. Bloc Évaluation et Formation
5. Bloc Carrière
6. Bloc Discipline
7. Bloc Rupture (STC, contrat PDF)
8. Routes à ajouter
9. Fichiers à créer
10. Bugs du code existant à corriger
11. Ordre de travail conseillé

---

## 1. SQL commun

SQLite ne sait pas modifier une contrainte `CHECK` ni retirer un `NOT NULL`. Donc :

- **Partie A** = on **remplace** 4 définitions de tables dans `base_recrutement.sql`, puis on **recrée la base** (le seed est rejouable).
- **Partie B** = nouveau script `migration_blocs_8_12.sql` à passer **après** le schéma et **avant** `donnees.sql`.
- **Partie C** = petites corrections du seed.

### 1.A Tables à remplacer dans `base_recrutement.sql`

**1) `utilisateur`** : ajouter le rôle `EMPLOYE` (une seule ligne change).

```sql
role VARCHAR(30) NOT NULL CHECK (role IN ('DEPARTEMENT','RH','DIRECTION','CANDIDAT','ADMINISTRATEUR','FINANCE','EMPLOYE')),
```

**2) `contrat`** : `id_candidature` devient facultatif (embauche directe sans candidature), ajout du poste, de l'origine et du contrat précédent, nouveaux statuts.

```sql
CREATE TABLE contrat (
    id_contrat INTEGER PRIMARY KEY,
    id_candidature BIGINT UNIQUE REFERENCES candidature(id_candidature),      -- NULL autorisé
    id_poste BIGINT REFERENCES poste(id_poste),
    origine VARCHAR(20) NOT NULL DEFAULT 'RECRUTEMENT'
        CHECK (origine IN ('RECRUTEMENT','DIRECT','PROMOTION','CHANGEMENT_POSTE','CHANGEMENT_STATUT','MUTATION')),
    id_contrat_precedent BIGINT REFERENCES contrat(id_contrat),
    reference VARCHAR(80) NOT NULL UNIQUE,
    type_contrat VARCHAR(20) NOT NULL CHECK (type_contrat IN ('CE', 'CDD', 'CDI')),
    date_debut DATE NOT NULL,
    date_fin DATE,
    salaire NUMERIC(14,2) NOT NULL CHECK (salaire >= 0),
    periodicite_salaire VARCHAR(30),
    lieu_travail TEXT,
    missions TEXT,
    horaires TEXT,
    conges_absences TEXT,
    statut VARCHAR(30) NOT NULL DEFAULT 'BROUILLON'
        CHECK (statut IN ('BROUILLON','A_SIGNER','SIGNE','ANNULE','REMPLACE','TERMINE')),
    pdf_nom VARCHAR(255),
    pdf_type_mime VARCHAR(120),
    pdf_donnees BLOB,
    date_signature TIMESTAMP,
    CHECK (date_fin IS NULL OR date_fin >= date_debut),
    CHECK (origine <> 'RECRUTEMENT' OR id_candidature IS NOT NULL)
);
```

**3) `heure_supplementaire`** : on stocke l'heure de début/fin ; le code calcule jour/nuit ; la clé unique inclut `nuit` (une journée peut avoir une ligne "jour" et une ligne "nuit"). **Supprimer** aussi la ligne `ALTER TABLE heure_supplementaire ADD COLUMN nuit ...` du bloc "Données manquantes".

```sql
CREATE TABLE heure_supplementaire (
    id_heure_supplementaire INTEGER PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employe(id_employe) ON DELETE CASCADE,
    date_travail DATE NOT NULL,
    heure_debut TIME,
    heure_fin TIME,
    heures NUMERIC(5,2) NOT NULL CHECK (heures > 0),
    nuit BOOLEAN NOT NULL DEFAULT 0,
    motif TEXT,
    statut VARCHAR(20) NOT NULL DEFAULT 'A_VALIDER'
        CHECK (statut IN ('A_VALIDER', 'VALIDE', 'REFUSEE')),
    id_validateur BIGINT REFERENCES utilisateur(id_utilisateur),
    cree_par BIGINT REFERENCES utilisateur(id_utilisateur),
    UNIQUE (id_employe, date_travail, nuit)
);
```

**4) `mouvement_carriere`** : nouveau type `CHANGEMENT_POSTE`, ids de poste/contrat, statut du mouvement (planifié si date future).

```sql
CREATE TABLE mouvement_carriere (
    id_mouvement INTEGER PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employe(id_employe) ON DELETE CASCADE,
    type_mouvement VARCHAR(30) NOT NULL
        CHECK(type_mouvement IN ('PROMOTION','MUTATION','CHANGEMENT_STATUT','REVISION_SALAIRE','AFFECTATION','CHANGEMENT_POSTE')),
    ancien_poste VARCHAR(150),
    nouveau_poste VARCHAR(150),
    ancien_id_poste BIGINT REFERENCES poste(id_poste),
    nouveau_id_poste BIGINT REFERENCES poste(id_poste),
    ancien_salaire NUMERIC(14,2),
    nouveau_salaire NUMERIC(14,2),
    id_departement BIGINT REFERENCES departement(id_departement),
    id_ancien_contrat BIGINT REFERENCES contrat(id_contrat),
    id_nouveau_contrat BIGINT REFERENCES contrat(id_contrat),
    date_effet DATE NOT NULL,
    date_fin DATE,
    motif TEXT,
    statut VARCHAR(20) NOT NULL DEFAULT 'APPLIQUE' CHECK(statut IN ('PLANIFIE','APPLIQUE','ANNULE')),
    id_auteur BIGINT REFERENCES utilisateur(id_utilisateur),
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_mouvement_employe_date ON mouvement_carriere(id_employe,date_effet);
```

### 1.B Script `migration_blocs_8_12.sql` (à créer tel quel)

```sql
PRAGMA foreign_keys = ON;
BEGIN;

-- ===== Employé : poste actuel et régime de travail =====
ALTER TABLE employe ADD COLUMN id_poste   BIGINT REFERENCES poste(id_poste);
ALTER TABLE employe ADD COLUMN id_regime  BIGINT REFERENCES regime_travail(id_regime);

-- ===== PAIE =====
-- Retards : saisie automatique, justification, validation
ALTER TABLE retard ADD COLUMN automatique BOOLEAN NOT NULL DEFAULT 0;
ALTER TABLE retard ADD COLUMN date_justification TIMESTAMP;
ALTER TABLE retard ADD COLUMN id_valideur BIGINT REFERENCES utilisateur(id_utilisateur);

-- Rubriques : catégorie pour que le calcul ne dépende plus du code texte
ALTER TABLE rubrique_paie ADD COLUMN categorie VARCHAR(20) NOT NULL DEFAULT 'AUTRE';
UPDATE rubrique_paie SET categorie = CASE code
  WHEN 'INDEMNITE' THEN 'INDEMNITE' WHEN 'INDEMNITE_NON_IMPOSABLE' THEN 'INDEMNITE'
  WHEN 'PRIME' THEN 'PRIME' WHEN 'RAPPEL' THEN 'RAPPEL' WHEN 'RETENUE' THEN 'AVANCE'
  WHEN 'HS_30' THEN 'HS' WHEN 'HS_50' THEN 'HS' WHEN 'DIMANCHE_NUIT' THEN 'HS'
  ELSE 'AUTRE' END;
-- categories : INDEMNITE | PRIME | RAPPEL | AVANCE | HS (calculées, non saisissables) | AUTRE
INSERT OR IGNORE INTO rubrique_paie(code,libelle,nature,imposable,categorie) VALUES
 ('INDEMNITE_LOGEMENT','Indemnité de logement','GAIN',1,'INDEMNITE'),
 ('INDEMNITE_TRANSPORT','Indemnité de transport','GAIN',1,'INDEMNITE'),
 ('INDEMNITE_REPRESENTATION','Indemnité de représentation','GAIN',1,'INDEMNITE'),
 ('PRIME_ANCIENNETE','Prime d''ancienneté','GAIN',1,'PRIME');

-- Bulletin : toutes les retenues détaillées + prorata + paiement via STC
ALTER TABLE bulletin_paie ADD COLUMN jours_travailles NUMERIC(5,2) NOT NULL DEFAULT 30;
ALTER TABLE bulletin_paie ADD COLUMN retenue_prorata NUMERIC(14,2) NOT NULL DEFAULT 0;
ALTER TABLE bulletin_paie ADD COLUMN retenue_retard NUMERIC(14,2) NOT NULL DEFAULT 0;
ALTER TABLE bulletin_paie ADD COLUMN retenue_mise_a_pied NUMERIC(14,2) NOT NULL DEFAULT 0;
ALTER TABLE bulletin_paie ADD COLUMN indemnites_non_imposables NUMERIC(14,2) NOT NULL DEFAULT 0;
ALTER TABLE bulletin_paie ADD COLUMN autres_retenues NUMERIC(14,2) NOT NULL DEFAULT 0;
ALTER TABLE bulletin_paie ADD COLUMN inclus_stc BOOLEAN NOT NULL DEFAULT 0;

-- ===== PARAMÈTRES RH (page unique, modifiable facilement) =====
CREATE TABLE parametre_rh (
    id_parametre_rh INTEGER PRIMARY KEY,
    code VARCHAR(60) NOT NULL UNIQUE,
    groupe VARCHAR(20) NOT NULL CHECK(groupe IN ('FORMATION','CARRIERE','DISCIPLINE','RUPTURE','PAIE')),
    libelle VARCHAR(180) NOT NULL,
    valeur NUMERIC(12,4) NOT NULL,
    unite VARCHAR(20),
    actif BOOLEAN NOT NULL DEFAULT 1,
    modifie_par BIGINT REFERENCES utilisateur(id_utilisateur),
    date_modification TIMESTAMP
);
INSERT OR IGNORE INTO parametre_rh(code,groupe,libelle,valeur,unite) VALUES
 ('SEUIL_ECART_FORMATION','FORMATION','Proposer une formation si écart (attendu - acquis) supérieur à',1,'niveaux'),
 ('CARR_PROMO_ANCIENNETE_ANS','CARRIERE','Promotion : ancienneté minimale',10,'ans'),
 ('CARR_PROMO_NOTE_MIN','CARRIERE','Promotion ancienneté : dernière note minimale',12,'/20'),
 ('CARR_PROMO_PERF_NOTE_MIN','CARRIERE','Promotion performance : note minimale',16,'/20'),
 ('CARR_PROMO_PERF_ANCIENNETE_MOIS','CARRIERE','Promotion performance : ancienneté minimale',12,'mois'),
 ('CARR_AUGM_ANCIENNETE_MOIS','CARRIERE','Augmentation : durée depuis le dernier changement de salaire',24,'mois'),
 ('CARR_AUGM_NOTE_MIN','CARRIERE','Augmentation : note minimale',14,'/20'),
 ('CARR_AUGM_POURCENT','CARRIERE','Augmentation proposée',5,'%'),
 ('CARR_SURPERF_ECART','CARRIERE','Changement de poste : écart moyen inférieur ou égal à (négatif = trop performant)',-1,'niveaux'),
 ('CARR_DELAI_MIN_MOUVEMENT_MOIS','CARRIERE','Aucune proposition si un mouvement a eu lieu depuis moins de',12,'mois'),
 ('CARR_BLOQUE_SANCTION_MOIS','CARRIERE','Aucune proposition si sanction (blâme ou plus) depuis moins de',12,'mois'),
 ('CARR_DELAI_REPROPOSITION_MOIS','CARRIERE','Ne pas reproposer après un refus pendant',6,'mois'),
 ('DISC_DELAI_REPONSE_JOURS','DISCIPLINE','Délai de réponse proposé par défaut',5,'jours'),
 ('DISC_RECIDIVE_MOIS','DISCIPLINE','Fenêtre de récidive',12,'mois'),
 ('PREAVIS_DEMISSION_JOURS','RUPTURE','Préavis par défaut : démission',30,'jours'),
 ('PREAVIS_LICENCIEMENT_JOURS','RUPTURE','Préavis par défaut : licenciement économique',30,'jours'),
 ('INDEM_LICENCIEMENT_ECO_JOURS_AN','RUPTURE','Indemnité licenciement économique (VALEUR DE TEST à valider)',10,'jours/an'),
 ('INDEM_RETRAITE_JOURS_AN','RUPTURE','Indemnité de départ à la retraite (VALEUR DE TEST à valider)',10,'jours/an'),
 ('TOLERANCE_RETARD_MIN','PAIE','Tolérance avant d''enregistrer un retard',0,'minutes'),
 ('NUIT_DEBUT_HEURE','PAIE','Début du travail de nuit',22,'heure'),
 ('NUIT_FIN_HEURE','PAIE','Fin du travail de nuit',4,'heure');

-- ===== ÉVALUATION / FORMATION =====
ALTER TABLE evaluation_performance ADD COLUMN type_evaluation VARCHAR(20) NOT NULL DEFAULT 'PERIODIQUE'
    CHECK(type_evaluation IN ('INITIALE','PERIODIQUE','POST_FORMATION'));
ALTER TABLE evaluation_performance ADD COLUMN id_formation BIGINT REFERENCES formation(id_formation);
ALTER TABLE employe_competence ADD COLUMN id_evaluation_performance BIGINT REFERENCES evaluation_performance(id_evaluation_performance);

-- Photo figée des écarts à chaque évaluation validée (RG-TRANS-02)
CREATE TABLE ecart_competence (
    id_ecart INTEGER PRIMARY KEY,
    id_evaluation_performance BIGINT NOT NULL REFERENCES evaluation_performance(id_evaluation_performance) ON DELETE CASCADE,
    id_employe BIGINT NOT NULL REFERENCES employe(id_employe) ON DELETE CASCADE,
    id_competence BIGINT NOT NULL REFERENCES competence(id_competence),
    niveau_attendu INTEGER NOT NULL,
    niveau_acquis INTEGER NOT NULL,
    ecart INTEGER NOT NULL,                      -- attendu - acquis (positif = besoin, négatif = surperformance)
    date_calcul TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(id_evaluation_performance, id_competence)
);

CREATE TABLE proposition_formation (
    id_proposition_formation INTEGER PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employe(id_employe) ON DELETE CASCADE,
    id_competence BIGINT NOT NULL REFERENCES competence(id_competence),
    id_ecart BIGINT REFERENCES ecart_competence(id_ecart),
    ecart INTEGER NOT NULL,
    seuil_applique NUMERIC(6,2) NOT NULL,
    id_formation BIGINT REFERENCES formation(id_formation),
    statut VARCHAR(20) NOT NULL DEFAULT 'EN_ATTENTE'
        CHECK(statut IN ('EN_ATTENTE','ASSIGNEE','REFUSEE','SANS_OBJET')),
    id_decideur BIGINT REFERENCES utilisateur(id_utilisateur),
    commentaire TEXT,
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_decision TIMESTAMP
);

-- Apport d'une formation : écart avant (proposition) vs écart après (évaluation POST_FORMATION)
CREATE VIEW v_apport_formation AS
SELECT pf.id_proposition_formation, pf.id_employe, pf.id_competence, pf.id_formation,
       e1.ecart AS ecart_avant, e2.ecart AS ecart_apres, (e1.ecart - e2.ecart) AS apport
FROM proposition_formation pf
JOIN ecart_competence e1 ON e1.id_ecart = pf.id_ecart
JOIN evaluation_performance ev ON ev.id_employe = pf.id_employe
     AND ev.type_evaluation = 'POST_FORMATION' AND ev.id_formation = pf.id_formation
JOIN ecart_competence e2 ON e2.id_evaluation_performance = ev.id_evaluation_performance
     AND e2.id_competence = pf.id_competence;
-- apport > 0 : amélioration | = 0 : stable | < 0 : dégradation (le nouvel écart peut être sup ou inf au premier)

-- ===== CARRIÈRE =====
CREATE TABLE proposition_carriere (
    id_proposition_carriere INTEGER PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employe(id_employe) ON DELETE CASCADE,
    type_proposition VARCHAR(20) NOT NULL CHECK(type_proposition IN ('PROMOTION','AUGMENTATION','CHANGEMENT_POSTE')),
    code_regle VARCHAR(40) NOT NULL,
    justification TEXT NOT NULL,
    salaire_suggere NUMERIC(14,2),
    id_poste_suggere BIGINT REFERENCES poste(id_poste),
    fonction_suggeree VARCHAR(150),
    statut VARCHAR(20) NOT NULL DEFAULT 'EN_ATTENTE'
        CHECK(statut IN ('EN_ATTENTE','ACCEPTEE','REFUSEE','EXPIREE')),
    id_decideur BIGINT REFERENCES utilisateur(id_utilisateur),
    commentaire_decision TEXT,
    id_mouvement BIGINT REFERENCES mouvement_carriere(id_mouvement),
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_decision TIMESTAMP
);

-- ===== DISCIPLINE =====
ALTER TABLE incident ADD COLUMN gravite VARCHAR(20) NOT NULL DEFAULT 'MODEREE'
    CHECK(gravite IN ('MINEURE','MODEREE','GRAVE','TRES_GRAVE'));
ALTER TABLE incident ADD COLUMN categorie VARCHAR(30) NOT NULL DEFAULT 'AUTRE';
  -- RETARD | ABSENCE | FAUTE_PROFESSIONNELLE | COMPORTEMENT | SECURITE | AUTRE
ALTER TABLE incident ADD COLUMN decision_finale VARCHAR(20)
    CHECK(decision_finale IS NULL OR decision_finale IN ('SANCTION','CLASSE_SANS_SUITE'));
ALTER TABLE reponse_explication ADD COLUMN en_retard BOOLEAN NOT NULL DEFAULT 0;
ALTER TABLE reponse_explication ADD COLUMN id_auteur BIGINT REFERENCES utilisateur(id_utilisateur);
ALTER TABLE sanction ADD COLUMN nb_jours INTEGER NOT NULL DEFAULT 0 CHECK(nb_jours >= 0);
ALTER TABLE sanction ADD COLUMN commentaire_contestation TEXT;

-- Quelles sanctions sont possibles selon la gravité de l'incident (modifiable)
CREATE TABLE gravite_sanction_autorisee (
    gravite VARCHAR(20) NOT NULL CHECK(gravite IN ('MINEURE','MODEREE','GRAVE','TRES_GRAVE')),
    type_sanction VARCHAR(40) NOT NULL
        CHECK(type_sanction IN ('AVERTISSEMENT','BLAME','MISE_A_PIED','LICENCIEMENT','AUTRE')),
    PRIMARY KEY (gravite, type_sanction)
);
INSERT OR IGNORE INTO gravite_sanction_autorisee VALUES
 ('MINEURE','AVERTISSEMENT'),('MINEURE','AUTRE'),
 ('MODEREE','AVERTISSEMENT'),('MODEREE','BLAME'),('MODEREE','AUTRE'),
 ('GRAVE','BLAME'),('GRAVE','MISE_A_PIED'),('GRAVE','AUTRE'),
 ('TRES_GRAVE','MISE_A_PIED'),('TRES_GRAVE','LICENCIEMENT'),('TRES_GRAVE','AUTRE');

-- ===== RUPTURE =====
ALTER TABLE rupture_contrat ADD COLUMN id_sanction BIGINT REFERENCES sanction(id_sanction);
ALTER TABLE rupture_contrat ADD COLUMN id_validateur BIGINT REFERENCES utilisateur(id_utilisateur);
ALTER TABLE rupture_contrat ADD COLUMN date_validation TIMESTAMP;
ALTER TABLE solde_tout_compte ADD COLUMN id_bulletin_final BIGINT REFERENCES bulletin_paie(id_bulletin);
ALTER TABLE solde_tout_compte ADD COLUMN jours_conges_restants NUMERIC(6,2) NOT NULL DEFAULT 0;
ALTER TABLE solde_tout_compte ADD COLUMN pdf_nom VARCHAR(255);
ALTER TABLE solde_tout_compte ADD COLUMN pdf_type_mime VARCHAR(120);
ALTER TABLE solde_tout_compte ADD COLUMN pdf_donnees BLOB;
ALTER TABLE solde_tout_compte ADD COLUMN date_validation TIMESTAMP;

-- ===== Index =====
CREATE INDEX idx_ecart_employe ON ecart_competence(id_employe, id_competence);
CREATE INDEX idx_prop_formation_statut ON proposition_formation(statut, id_employe);
CREATE INDEX idx_prop_carriere_statut ON proposition_carriere(statut, id_employe);
CREATE INDEX idx_sanction_statut ON sanction(statut, type_sanction);
CREATE INDEX idx_hs_employe_date ON heure_supplementaire(id_employe, date_travail);

-- ===== Reprise des données existantes =====
UPDATE contrat SET id_poste = (
  SELECT d.id_poste FROM candidature ca
  JOIN offre_emploi o ON o.id_offre = ca.id_offre
  JOIN demande_recrutement d ON d.id_demande = o.id_demande
  WHERE ca.id_candidature = contrat.id_candidature)
WHERE id_poste IS NULL AND id_candidature IS NOT NULL;
UPDATE employe SET id_poste = (SELECT c.id_poste FROM contrat c WHERE c.id_contrat = employe.id_contrat);
UPDATE employe SET id_regime = CASE secteur WHEN 'AGRICOLE' THEN 2 ELSE 1 END;
UPDATE utilisateur SET role = 'EMPLOYE' WHERE id_utilisateur IN (
  SELECT pe.id_utilisateur FROM personne pe JOIN employe e ON e.id_personne = pe.id_personne
  WHERE e.statut <> 'PARTI');

COMMIT;
```

### 1.C Corrections du seed (`donnees.sql`)

- Supprimer la **première** ligne `contrat` d'id 7 (`'CTR-2026-001'` en doublon avec le contrat 1) : elle est ignorée de toute façon et crée de la confusion.
- Le seed met les comptes des salariés en `CANDIDAT` : le `UPDATE utilisateur SET role='EMPLOYE'` de la migration les corrige. Dans `dev/login-as`, ajouter `'EMPLOYE' => 'employe@test.local'` et vérifier que les emails de la table `$map` existent vraiment dans le seed (ils ne correspondent pas aujourd'hui : `admin@test.local` contre `admin.demo@entreprise.test`).
- Variables 3, 4 et 5 du seed (`HS_30`, `HS_50`, `DIMANCHE_NUIT`) : elles sont **ignorées** par le calcul (les HS viennent de `heure_supplementaire`). À garder uniquement comme démonstration ou à supprimer.

---

## 2. Transverse

### 2.1 Rôle ADMINISTRATEUR = accès à tout

Aujourd'hui seul le groupe `rh` accepte `ADMINISTRATEUR`. Solution la plus simple, en un seul endroit :

```php
// app/Filters/RoleFilter.php — tout début de before()
if (session()->get('role') === 'ADMINISTRATEUR') {
    return; // l'admin passe partout
}
```

Avec ça, plus besoin d'écrire `,ADMINISTRATEUR` dans chaque groupe. Mais tant que ce n'est pas fait, ajouter `ADMINISTRATEUR` aux groupes `departement`, `direction`, `candidat`, et aux deux groupes `api`.

### 2.2 Page admin de test (`admin/`)

- `Admin\AccueilController::index` → vue `admin/accueil` : une grille de cartes **par rôle**, chaque carte = un bouton vers une page sans paramètre.
- Contenu minimum de la grille :

| Section | Boutons |
|---|---|
| RH recrutement | conditions, demandes, offres, entretiens, tests, évaluations candidats, requêtes |
| RH dossiers | employés, **embauche directe**, congés, temps de travail |
| RH paie | modules-avances, **périodes de paie**, **variables / indemnités**, **heures supplémentaires**, **retards** |
| RH évaluation/formation | **compétences**, **performance**, **propositions de formation**, **assigner une formation**, **apport des formations**, **paramètres RH** |
| RH carrière | **mouvements**, **propositions de carrière** |
| RH discipline | **incidents**, **sanctions**, **réponses des employés** |
| RH ruptures | **ruptures**, STC |
| Direction | offres, candidatures, demandes, embauche directe |
| Département | postes, demandes |
| Candidat | offres, mes candidatures, requêtes, contrats |
| Employé | accueil, notifications, formations, discipline |
| Outils dev | `dev/login-as/<ROLE>` pour chaque rôle, `dev/logout` |

- Pour les tests : un tableau PHP `$liens = [['section'=>..., 'label'=>..., 'url'=>...]]` dans le contrôleur, la vue boucle dessus (facile à compléter).

### 2.3 Statuts : un seul fichier de règles

Créer `app/Libraries/Transitions.php` avec les transitions autorisées, utilisé par **tous** les boutons "changer d'état" (on refuse et on affiche un message si la transition n'existe pas). Chaque changement écrit dans `audit_rh` (ancienne valeur / nouvelle valeur).

| Objet (table.colonne) | Transitions autorisées | Qui | Où |
|---|---|---|---|
| `periode_paie.statut` | OUVERT → CALCUL (calcul) → VALIDE → PAIE → CLOTURE ; OUVERT/CALCUL → ANNULE | RH | page Périodes de paie (3.2) |
| `bulletin_paie.statut` | BROUILLON → VALIDE → PAIE (suivent la période) ; BROUILLON → ANNULE | RH | idem |
| `heure_supplementaire.statut` | A_VALIDER → VALIDE / REFUSEE | RH | page HS |
| `retard.statut` | A_TRAITER → JUSTIFIE → VALIDE ; A_TRAITER → VALIDE (non justifié, donc déduit) | RH | page Retards |
| `employe.statut` | ACTIF ↔ SUSPENDU ; ACTIF → PARTI (uniquement par la rupture) | RH | fiche employé (motif obligatoire + ligne `historique_employe`) |
| `contrat.statut` | BROUILLON → A_SIGNER → SIGNE ; SIGNE → REMPLACE (nouveau contrat) / TERMINE (rupture) ; BROUILLON/A_SIGNER → ANNULE | RH | fiche contrat |
| `formation.statut` | PLANIFIEE → EN_COURS → TERMINEE ; PLANIFIEE/EN_COURS → ANNULEE | RH | page formation |
| `formation_participant.statut` | INSCRIT → PRESENT → TERMINE ; INSCRIT/PRESENT → ABANDON | RH | page formation |
| `evaluation_performance.statut` | BROUILLON → VALIDE → ARCHIVE | RH | page performance |
| `objectif.statut` | OUVERT → EN_COURS → ATTEINT / NON_ATTEINT ; tout → ANNULE | RH | page performance |
| `proposition_formation.statut` | EN_ATTENTE → ASSIGNEE / REFUSEE ; → SANS_OBJET (auto) | RH | propositions |
| `mouvement_carriere.statut` | PLANIFIE → APPLIQUE (auto à la date) / ANNULE | RH | page carrière |
| `proposition_carriere.statut` | EN_ATTENTE → ACCEPTEE / REFUSEE / EXPIREE | **RH uniquement** | page carrière |
| `incident.statut` | SIGNALE → EXPLICATIONS → REPONSE → COMMISSION → DECIDE → SANCTION → CLOTURE (voir 6) | RH | détail incident |
| `sanction.statut` | NOTIFIEE → CONTESTEE → EXECUTOIRE / ANNULEE ; NOTIFIEE → EXECUTOIRE | RH (employé pour contester) | liste sanctions |
| `rupture_contrat.statut` | OUVERT → EN_COURS → VALIDEE → CLOTURE ; OUVERT/EN_COURS → ANNULEE | RH, Direction (VALIDEE) | détail rupture |
| `solde_tout_compte.statut` | BROUILLON → VALIDE → PAIE ; → ANNULE | RH | détail rupture |
| `checklist_offboarding.valeur_statut` | A_FAIRE → EN_COURS → TERMINE ; → NON_APPLICABLE | RH | détail rupture |

### 2.4 Notifications (table `notification` déjà existante)

Créer `App\Services\NotificationService::envoyer(int $idUtilisateur, string $titre, string $message, ?string $lien)` (un simple `insert`). À appeler pour :

- formation proposée / assignée à l'employé ;
- demande d'explications, sanction notifiée à l'employé ;
- nouvelles propositions de carrière ou de formation → tous les comptes RH ;
- embauche directe → le nouvel employé.

Pour retrouver le compte d'un employé : `employe.id_personne → personne.id_utilisateur`.

### 2.5 Espace employé (minimum, rôle `EMPLOYE`)

Sans cet espace, les notifications d'employé ne s'affichent nulle part, et la "réponse de l'employé" reste saisie par la RH (d'où son inutilité actuelle).

| Page | Contenu |
|---|---|
| `employe/` | résumé : notifications non lues, formations à venir, explications à fournir |
| `employe/notifications` | liste, marquer lue (copier celle du candidat) |
| `employe/formations` | formations assignées (dates, statut) |
| `employe/discipline` | mes incidents : demande d'explications, **formulaire de réponse** (texte + pièce jointe pdf/jpg/png ≤ 2 Mo), mes sanctions, bouton **Contester** |
| `employe/bulletins` (optionnel) | mes bulletins PDF (log d'accès obligatoire) |

Redirections à ajouter : `AuthController` après connexion, et la table `match` de `dev/login-as` → `EMPLOYE => '/employe'`.

### 2.6 Page "Paramètres RH" (`rh/parametres`)

Un seul écran avec les lignes de `parametre_rh` regroupées par `groupe` (onglets FORMATION, CARRIERE, DISCIPLINE, RUPTURE, PAIE). Chaque ligne : libellé, champ valeur, unité, case "actif", bouton Enregistrer.
Chaque modification : `UPDATE` + `modifie_par`/`date_modification` + ligne `audit_rh` (ancienne/nouvelle valeur).
Helper : `App\Libraries\ParametreRh::get('CODE', $defaut)` (lecture seule, un cache par requête).

Le **seuil de formation** doit aussi s'afficher et se modifier **directement** sur les pages "Performance" et "Propositions de formation" (petit formulaire en haut) : même méthode, même table.

---

## 3. Bloc Paie

### 3.1 Embauche directe (Direction et RH)

**Pages** : `rh/employes/nouveau` et `direction/employes/nouveau` (même contrôleur `Rh\EmbaucheController`, deux entrées de route).

Formulaire en 3 étapes sur une page :

**Étape 1 — La personne**
- Option A : choisir une personne existante (liste filtrable ; exclure celles qui ont déjà un employé ACTIF).
- Option B : "Nouvelle personne" → email (unique), mot de passe temporaire (`password_hash`), nom, prénom, téléphone, adresse, date de naissance → crée `utilisateur` (rôle `EMPLOYE`) **puis** `personne`.

**Étape 2 — Le contrat**
- Option A : choisir un contrat existant (`statut = 'SIGNE'` et non utilisé : `id_contrat NOT IN (SELECT id_contrat FROM employe)`).
- Option B : créer un contrat : type (CE/CDD/CDI), date début, date fin (obligatoire si CDD/CE), salaire, périodicité, lieu, missions, horaires, congés, **poste** (liste `poste`). Insertion avec `id_candidature = NULL`, `origine = 'DIRECT'`, `statut = 'SIGNE'`, `date_signature = now`, référence auto `CTR-AAAA-NNN` (max + 1), historique dans `historique_contrat`, PDF généré (7.5).

**Étape 3 — L'employé**
- Département, fonction, catégorie (1A, 4A, 5B, HC...), secteur, N° CNaPS, régime de travail, date d'embauche (par défaut = début du contrat).
- Matricule automatique : `EMP-` + (max(id) + 1 sur 3 chiffres).

**À l'enregistrement (une seule transaction, `transBegin/transCommit`)** :
1. (si besoin) `utilisateur` + `personne`
2. (si besoin) `contrat` + `historique_contrat`
3. `employe` (avec `id_poste`, `id_regime`)
4. `affectation` (date_debut = date d'embauche)
5. `historique_employe` (NULL → ACTIF, "Embauche directe")
6. `historique_salaire` (motif "Embauche directe")
7. `solde_conge` ANNUEL de l'année en cours (acquis 0)
8. **Période de paie du mois d'embauche : créée si absente (voir 3.2)**
9. `audit_rh` + notification au nouvel employé
10. Si échec : rollback complet et message d'erreur.

### 3.2 Périodes de paie

**Page** `rh/paie/periodes` :
- Liste de toutes les périodes avec statut et boutons selon le tableau 2.3 : *Calculer*, *Valider*, *Marquer payée*, *Clôturer*, *Annuler*.
- Formulaire "Ouvrir une période" : mois, année, date de paiement (par défaut dernier jour du mois). Refuse un doublon (`UNIQUE(mois, annee)`).
- **Ouverture automatique** : `PeriodePaieService::assurer(mois, annee)` fait `INSERT OR IGNORE` avec statut `OUVERT`. Appelé :
  - à chaque embauche (directe ou via recrutement) pour le mois de `date_embauche` ;
  - au clic sur "Calculer" si la période n'existe pas encore.
- Si la période du mois d'embauche est déjà `VALIDE`/`PAIE`/`CLOTURE` : afficher un avertissement "période verrouillée, le salaire du mois sera régularisé par une variable RAPPEL le mois suivant".
- Dans la liste déroulante de la page paie, ne proposer que les périodes `OUVERT`/`CALCUL`.
- Clôturer, payer et valider se font **dans l'espace RH** (pas Finance), comme demandé.
- Passer la période en `PAIE` met aussi les bulletins `VALIDE` en `PAIE` ; `CLOTURE` n'est possible que si tous les bulletins sont `PAIE`.

### 3.3 Variables de paie : indemnités, primes, rappels, avances

**Page** `rh/paie/variables` (filtres : période, employé, catégorie).

Formulaire : employé, période (OUVERT ou CALCUL uniquement), rubrique (liste filtrée sur `categorie IN ('INDEMNITE','PRIME','RAPPEL','AVANCE')` — **jamais** `HS`), nombre, montant, motif.
- `UNIQUE(id_employe, id_periode, id_rubrique)` : si la ligne existe → proposer "Modifier" (upsert) au lieu d'une erreur.
- Modifier ou supprimer une variable est interdit si la période est VALIDE ou plus ; si la période est `CALCUL`, afficher "recalcul nécessaire".
- Raccourci "Ajouter une indemnité" qui préfiltre sur la catégorie INDEMNITE (logement, transport, représentation, non imposable).

**Changement dans `PaieService`** (le code actuel ne lit que les codes `INDEMNITE`, `RAPPEL`, `RETENUE`, donc `PRIME` et `INDEMNITE_NON_IMPOSABLE` du seed sont ignorés) :

| Catégorie | Rubrique imposable | Traitement |
|---|---|---|
| INDEMNITE, PRIME | oui | s'ajoute à `indemnites` (et donc au brut) |
| INDEMNITE, PRIME | non | s'ajoute à `indemnites_non_imposables` : hors brut, hors CNaPS/OSTIE/IRSA, **ajoutée au net** |
| RAPPEL | — | `rappels` |
| AVANCE | — | `autres_retenues` (déduite du net) |
| HS | — | ignorée (calculée depuis `heure_supplementaire`) |

### 3.4 Heures supplémentaires

**Page** `rh/paie/heures-supplementaires` : liste filtrable + formulaire + boutons Valider/Refuser.

Formulaire : employé, **date**, **heure de début**, **heure de fin**, motif. (Pas de case dimanche/nuit : c'est le code qui décide.)

Règles automatiques à l'enregistrement (`HeureSuppService`) :
1. Si `heure_fin <= heure_debut` → la fin est le lendemain.
2. Découper l'intervalle en morceaux **par jour civil** puis, dans chaque jour, en morceaux **nuit** (de `NUIT_DEBUT_HEURE` à `NUIT_FIN_HEURE`, paramétrables) et **jour**.
3. Un enregistrement par morceau (`date_travail` du jour civil du morceau, `nuit` 0 ou 1, `heures` = durée). S'il existe déjà une ligne `(employé, date, nuit)` → additionner.
4. Le **dimanche** n'a rien à stocker : `PaieService::majorations()` le détecte déjà avec `date('N') === '7'`. Les coefficients restent ceux du calcul (HS 30 % / 50 %, dimanche ×2, nuit ×1,5, cumul multiplicatif).
5. Contrôle plafond : somme de la semaine ISO + nouvelles heures ≤ `PLAFOND_HS` (20 h) sinon refus avec message précis.
6. Statut : saisie par la RH → directement `VALIDE` avec `id_validateur` ; sinon `A_VALIDER`.
7. Aperçu en direct sous le formulaire : "Dimanche : oui/non — Nuit : x h — Coefficient estimé ×y".

### 3.5 Retards

**Page** `rh/temps-travail/retards` (la route `temps-travail/retard` existe déjà, à compléter).

**Saisie manuelle** : employé, date, minutes **ou** heure d'arrivée réelle, motif, justifié (oui/non).
- S'il n'existe pas de `pointage` pour ce jour, en créer un (`source = MANUEL`, `heure_entree` = début prévu + minutes).
- Créer le `retard` (`UNIQUE(id_pointage)`), plus une ligne `anomalie_pointage` type `RETARD`.

**Justification** (bouton sur chaque ligne) : texte du justificatif → `justifie = 1`, `statut = JUSTIFIE`, `date_justification`. La RH clique ensuite **Valider** → `VALIDE` + `id_valideur`. Un retard non justifié se valide aussi (il sera déduit).

**Création automatique au pointage** (dans `TempsTravailController::pointer`, dès qu'une `heure_entree` est saisie) :

```php
// 1. horaire prévu : planning du jour, sinon premier horaire actif du régime de l'employé
$debutPrevu = strtotime($date . ' ' . $horaire['heure_debut']);
$minutes    = intdiv(strtotime($heureEntree) - $debutPrevu, 60);
$tol        = ParametreRh::get('TOLERANCE_RETARD_MIN', 0);

if ($minutes > $tol) {
    // upsert dans retard : minutes, automatique=1, justifie=0, statut='A_TRAITER',
    // motif='Retard détecté au pointage' + anomalie_pointage RETARD
} else {
    // supprimer le retard automatique encore A_TRAITER de ce pointage
}
```

Exemple : entrée prévue 08:00, arrivée 09:15 → 75 minutes (1 h 15) dans `retard`, déduites de la paie tant que `justifie = 0`.

**Calcul de la déduction** : `retenueRetard()` existe déjà (minutes × taux horaire / 60). Changement : le montant va dans `bulletin_paie.retenue_retard` et **diminue la base du mois** (donc le brut), comme une absence, au lieu d'être déduit du net après impôt.

### 3.6 Changements dans le calcul de paie (`PaieService`)

**a) Employés pris en compte**
- `ACTIF` **et** `PARTI` dont une rupture non `ANNULEE` a une `date_sortie >= début du mois`.
- Ne plus exclure les embauchés en cours de mois (prorata ci-dessous).

**b) Prorata des jours travaillés (embauche en cours de mois, rupture)**

```
debutTravail = max(début du mois, date_embauche)
finTravail   = min(fin du mois, date_sortie de la rupture non annulée, si elle existe)
jours        = nombre de jours calendaires entre debutTravail et finTravail (inclus)
si debutTravail = début du mois ET finTravail = fin du mois → jours_travailles = 30
sinon                                                      → jours_travailles = min(30, jours)
retenue_prorata = base × (30 - jours_travailles) / 30
```

Les absences, retards et mises à pied sont aussi bornés à `[debutTravail, finTravail]`. Après `date_sortie`, plus aucun bulletin.

**c) Nouvelle chaîne de calcul**

```
base_mois = base - retenue_absence - retenue_retard - retenue_mise_a_pied - retenue_prorata   (min 0)
brut      = base_mois + indemnités imposables + rappels + majorations
CNaPS / OSTIE / IRSA : inchangés
net       = brut - (CNaPS + OSTIE + IRSA + autres_retenues) + indemnités non imposables
```

**d) Mise à pied** : pour chaque `sanction` de type `MISE_A_PIED` avec statut `NOTIFIEE` ou `EXECUTOIRE` : jours = intersection de `[date_effet, date_effet + nb_jours - 1]` avec `[debutTravail, finTravail]` ; retenue = jours × (base / 30).

**e) Lignes de bulletin à ajouter** : `1011` Retenue retards, `1012` Retenue mise à pied (k j), `1013` Prorata jours travaillés (x/30), `2900` Indemnités non imposables (gain hors brut), `6000` Acomptes (existant).

**f) Employé sorti** : si une rupture est active sur la période, `inclus_stc = 1` : le dernier bulletin est un document de calcul, son net est repris dans le STC (7.3) et **n'est pas payé deux fois**.

### 3.7 États et documents à adapter

- **Excel (`PaieDocumentService::etatPaieXlsx`)** : la colonne 11 « RETENUES SUR ABSENCE » = `retenue_absence + retenue_retard + retenue_mise_a_pied + retenue_prorata` ; ajouter en fin de tableau une colonne « dont indemnités non imposables » et une colonne « Payé via STC » (oui/non).
- **PDF bulletin (`bulletin_pdf`)** : afficher les nouvelles lignes (1011, 1012, 1013, 2900) et `jours_travailles` / 30 dans l'en-tête.
- **Vue `modules_avances/index`** : la colonne PDF utilise `<th>` au lieu de `<td>` (à corriger) et le panneau Paie doit pointer vers les nouvelles pages (périodes, variables, HS, retards).

---

## 4. Bloc Évaluation et Formation

La partie **formation** (création, participants) reste telle quelle. Il manque **toute l'évaluation**, la **proposition automatique**, l'**assignation avec notification** et l'**apport de la formation**.

### 4.1 Référentiel (`rh/competences`)
- CRUD des `competence` (code unique, libellé, description, actif).
- Matrice **poste × compétence** : pour chaque poste, ajouter/retirer des compétences avec `niveau_attendu` (1 à 5) dans `poste_competence`.

### 4.2 Évaluation de performance (`rh/performance`)

- **Liste** filtrable : employé, période, statut, type (INITIALE, PERIODIQUE, POST_FORMATION).
- **Nouvelle évaluation** (`rh/performance/nouvelle/(:num)` avec l'id employé) :
  - période (ex. `S1-2027`), type, note globale 0-20, commentaire ;
  - un tableau **une ligne par compétence du poste de l'employé** (`employe.id_poste → poste_competence`) : compétence | attendu | dernier niveau connu | **niveau acquis (1-5)** ;
  - possibilité d'ajouter une compétence hors poste ;
  - zone "Objectifs" : mise à jour du `statut` et du `resultat` des objectifs de la période.
- **Enregistrer** = `evaluation_performance` en `BROUILLON` + `employe_competence` (`id_evaluation_performance` renseigné).
- **Valider** (statut `VALIDE`) déclenche, dans une transaction :
  1. calcul de `ecart = niveau_attendu - niveau_acquis` pour chaque compétence ;
  2. une ligne `ecart_competence` par compétence (photo figée) ;
  3. pour chaque écart **strictement supérieur** à `SEUIL_ECART_FORMATION` : création d'une `proposition_formation` (`EN_ATTENTE`, `seuil_applique` = seuil du moment), sauf si une proposition `EN_ATTENTE` existe déjà pour (employé, compétence) ;
  4. notification à la RH ("n propositions de formation") ;
  5. `audit_rh`.
- Une évaluation `VALIDE` n'est plus modifiable (RG-TRANS-02) ; une correction = nouvelle évaluation.
- **Détail** (`rh/performance/(:num)`) : tableau attendu / acquis / écart, badge rouge si l'écart dépasse le seuil, comparaison avec la précédente évaluation (↑ ↓ =), liste des propositions générées.

### 4.3 Seuil modifiable
Voir 2.6. Après un changement de seuil, bouton **« Recalculer les propositions »** : relit le dernier `ecart_competence` de chaque (employé, compétence), crée les propositions manquantes et passe en `SANS_OBJET` celles `EN_ATTENTE` dont l'écart ne dépasse plus le seuil.

### 4.4 Propositions de formation (`rh/formations/propositions`)
- Liste des propositions `EN_ATTENTE` : employé, compétence, écart, seuil appliqué, formations existantes liées à cette compétence (`formation.id_competence`).
- Actions :
  - **Assigner** : choisir une formation (ou créer rapidement une formation pour cette compétence) → `formation_participant` (`INSCRIT`, `niveau_avant` = niveau acquis actuel), proposition `ASSIGNEE` (+ `id_formation`, `id_decideur`), **notification employé** ;
  - **Refuser** : commentaire obligatoire → `REFUSEE`.

### 4.5 Assigner une formation à une personne (`rh/formations/assigner`)
- Page indépendante des propositions : choisir **une formation** (statut PLANIFIEE ou EN_COURS) et **un ou plusieurs employés** (cases à cocher).
- Pour chacun : `INSERT OR IGNORE` dans `formation_participant` (`UNIQUE(id_formation, id_employe)`), `niveau_avant` renseigné si la formation a une compétence.
- **Notification** à chaque employé : titre « Formation proposée : *titre* », message avec dates et prestataire, lien `/employe/formations`.
- Refuser silencieusement ceux déjà inscrits et les lister dans le message de retour.

### 4.6 Apport de la formation (évaluation post-formation)
- Sur la fiche d'une formation `TERMINEE`, pour chaque participant `TERMINE` : champ **niveau après** (1-5).
- Enregistrer → `formation_participant.niveau_apres` **et** une évaluation `POST_FORMATION` (`id_formation` renseigné, statut `VALIDE`) avec un `employe_competence` et un `ecart_competence` pour la compétence de la formation.
- `rh/formations/apport` : tableau lu depuis la vue `v_apport_formation` : employé, formation, **écart avant**, **écart après**, **apport** et un verdict :
  - apport > 0 → **Amélioration** ;
  - apport = 0 → **Stable** ;
  - apport < 0 → **Dégradation** (le nouvel écart peut être supérieur ou inférieur au premier).
- Si l'écart après dépasse encore le seuil → nouvelle `proposition_formation` automatique (même règle qu'en 4.2).
- Un écart **négatif** (employé au-dessus du niveau attendu) est conservé : il alimente les propositions de carrière (5.4).

---

## 5. Bloc Carrière

### 5.1 Principe
Aujourd'hui `creationCarriere()` insère une ligne dans `mouvement_carriere` et **ne change rien** ailleurs. Il faut un `CarriereService::appliquer($mouvement)` qui met à jour tout.

### 5.2 Formulaire de mouvement (`rh/carriere/mouvement`)

- Employé (liste de tous les ACTIF) ; le formulaire **affiche et préremplit** poste, salaire, département actuels (jamais saisis à la main : `ancien_*` viennent de la base).
- Champs affichés selon le type (JS `show/hide`) :

| Type | Champs | Ce qui change |
|---|---|---|
| PROMOTION | nouvelle fonction, nouveau salaire (optionnel), nouveau poste (optionnel) | fonction, salaire ; poste/contrat si le poste change |
| REVISION_SALAIRE | nouveau salaire (> ancien, sinon avertissement) | salaire uniquement |
| CHANGEMENT_POSTE | nouveau poste (`poste`), fonction, salaire (optionnel) | **id du poste + nouveau contrat** |
| MUTATION | nouveau département (+ poste optionnel) | département, affectation |
| CHANGEMENT_STATUT | nouveau type de contrat (CDD→CDI...) | **nouveau contrat** |
| AFFECTATION | département + fonction | affectation |

- Date d'effet obligatoire, motif obligatoire.
- Si `date_effet` > aujourd'hui → `statut = PLANIFIE` (rien n'est modifié maintenant). Sinon `APPLIQUE` et on exécute tout de suite.
- **Application des dates futures** : `CarriereService::appliquerEchus()` (applique tous les `PLANIFIE` dont `date_effet <= aujourd'hui`) appelé à l'ouverture de la page carrière et de l'accueil RH. Un CLI `php spark carriere:appliquer` est un plus, pas une obligation.

### 5.3 Ce que fait `appliquer()` (transaction unique)

**Cas A — Salaire seul** (REVISION_SALAIRE, ou PROMOTION sans changement de poste) :
1. `historique_salaire` (ancien, nouveau, motif, `date_effet`, `id_utilisateur`) ;
2. `UPDATE contrat SET salaire = nouveau` sur le contrat courant de l'employé (l'historique reste dans `historique_salaire`) ;
3. mouvement `APPLIQUE`, `id_ancien_contrat = id_nouveau_contrat`.

**Cas B — Changement de poste ou de statut** (nouveau contrat obligatoire) :
1. nouveau `contrat` : copie du contrat actuel, `id_contrat_precedent` = ancien, `origine` = `CHANGEMENT_POSTE` / `PROMOTION` / `CHANGEMENT_STATUT` / `MUTATION`, `id_candidature = NULL`, `date_debut = date_effet`, nouvelle référence, nouveau poste/salaire/type, `statut = SIGNE`, PDF généré ;
2. ancien contrat : `statut = REMPLACE`, `date_fin = date_effet - 1 jour` ;
3. `UPDATE employe SET id_contrat = nouveau, id_poste = nouveau_poste` ;
4. si salaire différent : `historique_salaire` ;
5. `historique_contrat` pour les deux contrats.

**Dans tous les cas** :
- Si la fonction ou le département change : fermer l'`affectation` en cours (`date_fin = date_effet - 1`) puis en ouvrir une (`date_debut = date_effet`). **Refuser** si cela créerait un chevauchement (règle RG bloc 10).
- Renseigner correctement `ancien_poste`/`nouveau_poste` (texte) et `ancien_id_poste`/`nouveau_id_poste`.
- `audit_rh` avec `objet_id` = id du mouvement (aujourd'hui `NULL`).
- Notification à l'employé.

### 5.4 Propositions de carrière (visibles **uniquement par la RH**)

**Page** `rh/carriere/propositions` (routes réservées au groupe `rh`) : liste `EN_ATTENTE` avec la **justification chiffrée**, boutons **Suivre** / **Refuser** (commentaire).
- **Suivre** : ouvre le formulaire de mouvement **prérempli** avec la proposition ; à l'enregistrement, la proposition passe `ACCEPTEE` + `id_mouvement`.
- **Refuser** : `REFUSEE` + commentaire ; la même proposition n'est pas régénérée avant `CARR_DELAI_REPROPOSITION_MOIS`.
- Bouton **« Recalculer les propositions »** (`PropositionCarriereService::generer()`) ; jamais affichées à l'employé ni à la Direction.

**Règles proposées (toutes les valeurs viennent de `parametre_rh`, désactivables par la case `actif`)** :

| Code | Type | Condition | Résultat suggéré |
|---|---|---|---|
| `R1_ANCIENNETE` | PROMOTION | ancienneté ≥ `CARR_PROMO_ANCIENNETE_ANS` (10) **et** dernière note ≥ `CARR_PROMO_NOTE_MIN` (12) | fonction supérieure |
| `R2_PERFORMANCE` | PROMOTION | dernière note ≥ `CARR_PROMO_PERF_NOTE_MIN` (16) **et** ancienneté ≥ `CARR_PROMO_PERF_ANCIENNETE_MOIS` (12) **et** écart moyen ≤ 0 | fonction supérieure |
| `R3_AUGMENTATION` | AUGMENTATION | dernier changement de salaire (`historique_salaire.date_effet`) ≥ `CARR_AUGM_ANCIENNETE_MOIS` (24) **et** note ≥ `CARR_AUGM_NOTE_MIN` (14) | salaire × (1 + `CARR_AUGM_POURCENT`/100) |
| `R4_SURPERFORMANCE` | CHANGEMENT_POSTE | écart moyen des compétences ≤ `CARR_SURPERF_ECART` (-1) = employé « trop performant » pour son poste | poste de `poste` dont toutes les compétences sont couvertes par l'employé (acquis ≥ attendu) avec la plus forte somme de niveaux attendus, différent du poste actuel |

**Garde-fous (aucune proposition si l'une de ces conditions est vraie)** :
- employé non `ACTIF` ou rupture active ;
- sanction `BLAME`, `MISE_A_PIED` ou `LICENCIEMENT` depuis moins de `CARR_BLOQUE_SANCTION_MOIS` ;
- mouvement `APPLIQUE` depuis moins de `CARR_DELAI_MIN_MOUVEMENT_MOIS` ;
- proposition `EN_ATTENTE` du même type déjà existante ;
- aucune évaluation `VALIDE` pour R2/R3/R4 (pas de données = pas de proposition).

La `justification` est un texte clair, par exemple : « Ancienneté 11 ans (seuil 10), note 15/20 (seuil 12) ». Les nouvelles propositions déclenchent une notification aux comptes RH.

### 5.5 Parcours et reconstitution de carrière (`rh/employes/(:num)/parcours`)
Champ « Situation au JJ/MM/AAAA » (RG-TRANS-03). Affiche pour cette date : poste, fonction, département (affectation), salaire (dernière ligne `historique_salaire` avec `date_effet <= date`), contrat en vigueur, plus la frise des mouvements.

---

## 6. Bloc Discipline

### 6.1 Ce qui ne va pas aujourd'hui
- Les formulaires « réponse » et « sanction » utilisent `incidents[0]` : ils ne marchent que pour le premier incident.
- La réponse de l'employé est saisie par la RH et n'est affichée nulle part.
- Aucun lien entre l'incident et le choix de la sanction ; une sanction ne change rien (ni paie, ni contrat).
- Pas de liste filtrable des sanctions ni des réponses.

### 6.2 Pages RH
| Page | Contenu |
|---|---|
| `rh/discipline/incidents` | liste filtrable (statut, gravité, catégorie, employé, dates) |
| `rh/discipline/incidents/(:num)` | **dossier** : frise incident → demande → réponse → décision → sanction ; actions selon le statut |
| `rh/discipline/sanctions` | liste filtrable : type, statut, employé, département, gravité, période de décision |
| `rh/discipline/reponses` | liste de **toutes les réponses des employés** : employé, incident, date, texte, en retard ?, pièce jointe (téléchargement) ; filtres employé, période, avec/sans pièce, en retard |

Chaque consultation de dossier/liste écrit dans `journal_acces` (`ressource = 'DOSSIER_DISCIPLINAIRE'`, RG-TRANS-04).

### 6.3 Workflow du dossier (boutons contextuels)

| Statut | Action disponible | Effet |
|---|---|---|
| SIGNALE | **Demander des explications** (délai par défaut `DISC_DELAI_REPONSE_JOURS`, texte prérempli) | `demande_explication` + notification employé → EXPLICATIONS |
| EXPLICATIONS | attente ; affichage du délai restant ; l'employé répond depuis son espace (2.5). La RH peut aussi **saisir une réponse papier** au nom de l'employé | réponse reçue → REPONSE ; marquée `en_retard` si après `date_limite` |
| REPONSE (ou EXPLICATIONS avec délai dépassé sans réponse) | **Passer en commission** (optionnel, date + commentaire) | → COMMISSION |
| REPONSE / COMMISSION / délai dépassé | **Décision** (6.4) | → DECIDE puis SANCTION, ou CLOTURE (classement) |
| SANCTION | gérer le statut de la sanction (6.5) | → CLOTURE quand la sanction est `EXECUTOIRE` ou `ANNULEE` |

**Condition de sanction** (remplace le test actuel « aucune réponse = interdit ») : une `demande_explication` notifiée doit exister **et** (une `reponse_explication` existe **ou** `date_limite` est dépassée). La **réponse est affichée en toute lumière** sur la page de décision.

### 6.4 Formulaire de décision
- Choix : **Classer sans suite** (`decision_finale = CLASSE_SANS_SUITE`, incident `CLOTURE`) ou **Sanctionner**.
- Les types de sanction proposés viennent de `gravite_sanction_autorisee` selon la **gravité de l'incident** (liste modifiable en base).
- À l'écran : gravité, réponse de l'employé, **historique des sanctions de l'employé** et alerte récidive si au moins une sanction dans les `DISC_RECIDIVE_MOIS` derniers mois.
- Champs : motif, `date_decision`, `date_effet`, et **`nb_jours` obligatoire (≥ 1) pour `MISE_A_PIED`**.
- À l'enregistrement : `sanction` (`NOTIFIEE`), incident → `SANCTION`, `decision_finale = SANCTION`, notification employé, audit.
- Le formulaire de signalement d'incident gagne les champs **catégorie** et **gravité**.

### 6.5 Effets des sanctions

| Type | Effet |
|---|---|
| AVERTISSEMENT, BLAME | enregistrés au dossier |
| **MISE_A_PIED** | `nb_jours` jours **déduits du salaire** : voir 3.6 d) ; ligne `1012` sur le bulletin |
| **LICENCIEMENT** | **crée automatiquement une rupture de contrat** (voir ci-dessous) |
| AUTRE | texte libre |

**Licenciement → rupture** (via `RuptureService::creer()`, le même code que le bloc Rupture) :
- `motif = LICENCIEMENT_FAUTE`, `date_notification = date_decision`, `date_sortie = date_effet`, `duree_preavis_jours = 0`, `id_sanction` renseigné, `statut = EN_COURS`.
- Même création que la rupture manuelle : checklist, brouillon de STC, attestations.
- Si l'employé a déjà une rupture (`UNIQUE(id_employe)`) : refuser la sanction avec un message clair.

**Statut de la sanction** : l'employé peut **Contester** (→ `CONTESTEE` + commentaire) ; la RH peut passer `EXECUTOIRE` (ou `ANNULEE`). Annuler une mise à pied déjà retenue sur une paie validée → créer une variable `RAPPEL` le mois suivant.

---

## 7. Bloc Rupture

### 7.1 Création d'une rupture (`rh/ruptures`)
Refactorer `creerRupture()` en `RuptureService::creer()` (appelée aussi par la discipline) :
- Date de sortie calculée par défaut : `date_notification + préavis` (`PREAVIS_DEMISSION_JOURS`, `PREAVIS_LICENCIEMENT_JOURS`) ; pour `FIN_CDD`, `date_sortie = contrat.date_fin`. Modifiable.
- Refuser si l'employé a déjà une rupture.
- Checklist standard (même libellés partout) : `Dernier bulletin de paie`, `Validation STC`, `Certificat de travail`, `Attestations sociales`, `Restitution matériel`, `Coupure des accès`, `Archivage dossier`.
- STC en brouillon **calculé** (7.3) et non plus égal au salaire.
- Attestations créées vides (références), PDF générés à la demande (7.4).

### 7.2 Cycle de vie
- `OUVERT` (saisie) → `EN_COURS` (préavis, checklist) → `VALIDEE` (Direction ou RH) → `CLOTURE` (checklist complète + STC `PAIE`) ; `ANNULEE` possible avant `VALIDEE`.
- `RuptureService::appliquerSorties()` (appelé comme `appliquerEchus()`) : pour chaque rupture `VALIDEE` dont `date_sortie <= aujourd'hui` :
  - `employe.statut = PARTI` + `historique_employe` ;
  - `contrat.statut = TERMINE` (et `date_fin` renseignée si vide) ;
  - fermeture de l'`affectation` en cours ;
  - `utilisateur.actif = 0` → **coupure des accès** ;
  - élément « Coupure des accès » de la checklist = `TERMINE`.
- Automatismes sur la checklist : « Validation STC » = TERMINE quand le STC est `VALIDE`, « Dernier bulletin » = TERMINE quand le bulletin existe, « Certificat de travail » quand le PDF est généré.
- Page détail `rh/ruptures/(:num)` : infos, checklist **modifiable** (statut, commentaire, `date_realisation`), STC, attestations, boutons de statut.

### 7.3 Bulletin et état de paie après une rupture
- Dernier bulletin = prorata jusqu'à `date_sortie` (voir 3.6 b), `inclus_stc = 1`.
- Aucun bulletin pour les mois suivants (l'employé n'est plus sélectionné).

### 7.4 Solde de tout compte (STC)
`StcService::calculer(int $idRupture)` (bouton **Recalculer** tant que `BROUILLON`) :

```
dernier_salaire    = salaire en vigueur à date_sortie
salaire_proratise  = net_a_payer du dernier bulletin (id_bulletin_final), sinon base × jours_travailles / 30
jours_conges_rest. = acquis + ajuste - pris   (solde_conge ANNUEL de l'année de sortie)
indemnite_conges   = jours_conges_restants × (salaire de base / 30)
indemnite_rupture  = selon le motif :
    DEMISSION, FIN_CDD, LICENCIEMENT_FAUTE → 0
    LICENCIEMENT_ECONOMIQUE → ancienneté (années) × INDEM_LICENCIEMENT_ECO_JOURS_AN × (base / 30)
    RETRAITE                → ancienneté (années) × INDEM_RETRAITE_JOURS_AN × (base / 30)
    RUPTURE_CONVENTIONNELLE, AUTRE → montant saisi à la main
avances            = variables AVANCE non remboursées (modifiable)
retenues           = saisie manuelle (matériel non restitué, etc.)
montant_net        = salaire_proratise + indemnite_conges + indemnite_rupture - avances - retenues
```

> Les paramètres d'indemnité sont des **valeurs de test** : à faire valider (code du travail, convention) avant tout usage réel.

- Page STC : tous les champs modifiables en brouillon, boutons **Valider** (`VALIDE`, `date_validation`) puis **Marquer payé** (`PAIE`). Plus aucune modification après `VALIDE` (RG-TRANS-02).
- **PDF du STC** (`rh/ruptures/(:num)/stc/pdf`, Dompdf) enregistré dans `pdf_donnees` : en-tête entreprise, identité employé, détail ligne par ligne, net en chiffres et en lettres (réutiliser `enLettres`), zone de signature. Log d'accès.

### 7.5 Documents PDF
**Contrat de travail en PDF** — `App\Services\ContratPdfService::generer(int $idContrat)` :
- contenu : en-tête entreprise (`config('Entreprise')`), référence, parties (employeur / identité du salarié depuis `personne`), poste, type de contrat, dates, salaire et périodicité, lieu, missions, horaires, congés/absences, **conditions** (`contrat_condition` ordonnées par `ordre_affichage`), blocs de signature, date de signature si `SIGNE` ;
- stockage dans `contrat.pdf_nom / pdf_type_mime / pdf_donnees` ;
- un contrat `SIGNE` déjà généré est **servi tel quel** (pas de régénération, RG-TRANS-02) ;
- routes : `rh/contrats/(:num)/pdf`, et le contrôleur candidat existant `telechargerPdf` appelle le même service ;
- généré automatiquement à la création d'un contrat (embauche directe, mouvement de carrière avec nouveau contrat).

**Certificat de travail et attestations CNaPS / OSTIE** : `AttestationService::generer(id_attestation)` (Dompdf), remplit `pdf_donnees`, route `rh/ruptures/(:num)/attestations/(:num)/pdf`, boutons « Générer » et « Télécharger » ; référence existante conservée.

---

## 8. Routes à ajouter

```php
// ---- Filtres : voir 2.1 (ajouter ADMINISTRATEUR à chaque groupe si le filtre n'est pas modifié)
// ---- Supprimer le groupe 'direction' dupliqué (celui qui n'a que '/').

$routes->group('rh', ['filter' => 'role:RH,ADMINISTRATEUR'], static function ($routes) {

    // Embauche directe
    $routes->get('employes/nouveau', 'Rh\EmbaucheController::nouveau');
    $routes->post('employes/creer', 'Rh\EmbaucheController::creer');
    $routes->get('employes/(:num)/parcours', 'Rh\CarriereController::parcours/$1');
    $routes->post('employes/(:num)/statut', 'Rh\EmployesController::changerStatut/$1');
    $routes->get('contrats/(:num)/pdf', 'Rh\ContratsController::pdf/$1');
    $routes->post('contrats/(:num)/statut', 'Rh\ContratsController::changerStatut/$1');

    // Paie
    $routes->get('paie/periodes', 'Rh\PeriodesPaieController::index');
    $routes->post('paie/periodes', 'Rh\PeriodesPaieController::creer');
    $routes->post('paie/periodes/(:num)/statut', 'Rh\PeriodesPaieController::statut/$1');
    $routes->get('paie/variables', 'Rh\VariablesPaieController::index');
    $routes->post('paie/variables', 'Rh\VariablesPaieController::enregistrer');
    $routes->post('paie/variables/(:num)/supprimer', 'Rh\VariablesPaieController::supprimer/$1');
    $routes->get('paie/heures-supplementaires', 'Rh\HeuresSuppController::index');
    $routes->post('paie/heures-supplementaires', 'Rh\HeuresSuppController::enregistrer');
    $routes->post('paie/heures-supplementaires/(:num)/decision', 'Rh\HeuresSuppController::decision/$1');
    $routes->get('temps-travail/retards', 'Rh\TempsTravailController::retards');
    $routes->post('temps-travail/retards/(:num)/justifier', 'Rh\TempsTravailController::justifier/$1');
    $routes->post('temps-travail/retards/(:num)/valider', 'Rh\TempsTravailController::validerRetard/$1');

    // Paramètres
    $routes->get('parametres', 'Rh\ParametresRhController::index');
    $routes->post('parametres', 'Rh\ParametresRhController::enregistrer');

    // Évaluation et formation
    $routes->get('competences', 'Rh\CompetencesController::index');
    $routes->post('competences', 'Rh\CompetencesController::enregistrer');
    $routes->post('competences/poste', 'Rh\CompetencesController::lierPoste');
    $routes->get('performance', 'Rh\PerformanceController::index');
    $routes->get('performance/nouvelle/(:num)', 'Rh\PerformanceController::nouvelle/$1');
    $routes->post('performance/enregistrer', 'Rh\PerformanceController::enregistrer');
    $routes->post('performance/(:num)/valider', 'Rh\PerformanceController::valider/$1');
    $routes->get('performance/(:num)', 'Rh\PerformanceController::detail/$1');
    $routes->get('formations/propositions', 'Rh\FormationsController::propositions');
    $routes->post('formations/propositions/(:num)/assigner', 'Rh\FormationsController::assignerProposition/$1');
    $routes->post('formations/propositions/(:num)/refuser', 'Rh\FormationsController::refuserProposition/$1');
    $routes->post('formations/propositions/recalculer', 'Rh\FormationsController::recalculer');
    $routes->get('formations/assigner', 'Rh\FormationsController::assigner');
    $routes->post('formations/assigner', 'Rh\FormationsController::enregistrerAssignation');
    $routes->post('formations/(:num)/statut', 'Rh\FormationsController::statut/$1');
    $routes->post('formations/(:num)/niveau-apres', 'Rh\FormationsController::niveauApres/$1');
    $routes->get('formations/apport', 'Rh\FormationsController::apport');

    // Carrière
    $routes->get('carriere', 'Rh\CarriereController::index');
    $routes->get('carriere/mouvement', 'Rh\CarriereController::formulaire');
    $routes->post('carriere/mouvement', 'Rh\CarriereController::enregistrer');
    $routes->post('carriere/(:num)/annuler', 'Rh\CarriereController::annuler/$1');
    $routes->get('carriere/propositions', 'Rh\CarriereController::propositions');
    $routes->post('carriere/propositions/generer', 'Rh\CarriereController::generer');
    $routes->post('carriere/propositions/(:num)/suivre', 'Rh\CarriereController::suivre/$1');
    $routes->post('carriere/propositions/(:num)/refuser', 'Rh\CarriereController::refuser/$1');

    // Discipline
    $routes->get('discipline/incidents', 'Rh\DisciplineController::incidents');
    $routes->get('discipline/incidents/(:num)', 'Rh\DisciplineController::dossier/$1');
    $routes->post('discipline/incidents', 'Rh\DisciplineController::creerIncident');
    $routes->post('discipline/incidents/(:num)/explications', 'Rh\DisciplineController::demanderExplication/$1');
    $routes->post('discipline/incidents/(:num)/reponse', 'Rh\DisciplineController::saisirReponse/$1');
    $routes->post('discipline/incidents/(:num)/commission', 'Rh\DisciplineController::commission/$1');
    $routes->post('discipline/incidents/(:num)/decision', 'Rh\DisciplineController::decider/$1');
    $routes->get('discipline/sanctions', 'Rh\DisciplineController::sanctions');
    $routes->post('discipline/sanctions/(:num)/statut', 'Rh\DisciplineController::statutSanction/$1');
    $routes->get('discipline/reponses', 'Rh\DisciplineController::reponses');
    $routes->get('discipline/reponses/(:num)/piece', 'Rh\DisciplineController::piece/$1');

    // Ruptures
    $routes->get('ruptures', 'Rh\RupturesController::index');
    $routes->post('ruptures', 'Rh\RupturesController::creer');
    $routes->get('ruptures/(:num)', 'Rh\RupturesController::show/$1');
    $routes->post('ruptures/(:num)/statut', 'Rh\RupturesController::statut/$1');
    $routes->post('ruptures/(:num)/checklist/(:num)', 'Rh\RupturesController::checklist/$1/$2');
    $routes->post('ruptures/(:num)/stc/calculer', 'Rh\RupturesController::calculerStc/$1');
    $routes->post('ruptures/(:num)/stc/statut', 'Rh\RupturesController::statutStc/$1');
    $routes->get('ruptures/(:num)/stc/pdf', 'Rh\RupturesController::stcPdf/$1');
    $routes->get('ruptures/(:num)/attestations/(:num)/pdf', 'Rh\RupturesController::attestationPdf/$1/$2');
});

// Embauche directe côté Direction (même contrôleur)
$routes->group('direction', ['filter' => 'role:DIRECTION,FINANCE,ADMINISTRATEUR'], static function ($routes) {
    $routes->get('employes/nouveau', 'Rh\EmbaucheController::nouveau');
    $routes->post('employes/creer', 'Rh\EmbaucheController::creer');
    $routes->post('ruptures/(:num)/statut', 'Rh\RupturesController::statut/$1'); // validation (VALIDEE)
});

// Espace employé
$routes->group('employe', ['filter' => 'role:EMPLOYE,ADMINISTRATEUR'], static function ($routes) {
    $routes->get('/', 'Employe\AccueilController::index');
    $routes->get('notifications', 'Employe\NotificationsController::index');
    $routes->post('notifications/(:num)/marquer-lue', 'Employe\NotificationsController::marquerLue/$1');
    $routes->post('notifications/tout-marquer-lu', 'Employe\NotificationsController::toutMarquerLu');
    $routes->get('formations', 'Employe\FormationsController::index');
    $routes->get('discipline', 'Employe\DisciplineController::index');
    $routes->post('discipline/(:num)/reponse', 'Employe\DisciplineController::repondre/$1');
    $routes->post('discipline/sanctions/(:num)/contester', 'Employe\DisciplineController::contester/$1');
    $routes->get('bulletins', 'Employe\BulletinsController::index');          // optionnel
});

// Admin
$routes->group('admin', ['filter' => 'role:ADMINISTRATEUR'], static function ($routes) {
    $routes->get('/', 'Admin\AccueilController::index');
});
```

> Attention : `rh/evaluations` existe déjà pour les **évaluations de candidats**. Les évaluations de **performance** utilisent `rh/performance` pour éviter le conflit.

---

## 9. Fichiers à créer

**Contrôleurs** : `Rh\EmbaucheController`, `Rh\PeriodesPaieController`, `Rh\VariablesPaieController`, `Rh\HeuresSuppController`, `Rh\ParametresRhController`, `Rh\CompetencesController`, `Rh\PerformanceController`, `Rh\FormationsController`, `Rh\CarriereController`, `Rh\DisciplineController`, `Rh\RupturesController`, `Rh\ContratsController`, `Employe\{Accueil,Notifications,Formations,Discipline,Bulletins}Controller`, `Admin\AccueilController` (à compléter). À modifier : `Rh\TempsTravailController` (auto-retard, justification), `Rh\EmployesController` (statut), `Rh\ModulesAvancesController` (retirer ce qui est déplacé, garder consultation/état/bulletin).

**Services (`app/Services/`)** : `PeriodePaieService`, `HeureSuppService`, `NotificationService`, `EvaluationService` (écarts, propositions, apport), `CarriereService`, `PropositionCarriereService`, `DisciplineService`, `RuptureService`, `StcService`, `ContratPdfService`, `AttestationService`. À modifier : `PaieService`, `PaieDocumentService`.

**Libraries** : `Transitions`, `ParametreRh`.

**Vues** : `rh/embauche/*`, `rh/paie/{periodes,variables,heures_sup,retards}`, `rh/parametres`, `rh/competences`, `rh/performance/*`, `rh/formations/{propositions,assigner,apport}`, `rh/carriere/*`, `rh/discipline/*`, `rh/ruptures/*`, `rh/contrats/contrat_pdf`, `rh/ruptures/{stc_pdf,attestation_pdf}`, `employe/*`, `admin/accueil`. Ajouter les entrées dans `rh/layout` (menu).

**Aucun fichier dans `app/Models/`.**

---

## 10. Bugs du code existant à corriger

1. Groupe `direction` déclaré **deux fois** dans `Routes.php`.
2. `modules_avances/index` : cellule PDF en `<th>`, formulaires discipline basés sur `incidents[0]`.
3. `PaieService` ignore `PRIME` et `INDEMNITE_NON_IMPOSABLE` ; pas de prorata ; exclut les `PARTI` du mois de sortie ; déduit les retards **après impôt** au lieu de réduire le brut.
4. `validerPaie()` ne gère que CALCUL → VALIDE : il manque PAIE et CLOTURE (fait dans la RH).
5. `creationCarriere()` n'actualise ni salaire, ni poste, ni contrat, ni affectation ; `ancien_poste` / `ancien_salaire` jamais renseignés ; `audit` sans `objet_id`.
6. `creerRupture()` : checklist avec d'autres libellés que le seed, STC = salaire brut sans calcul, ne passe jamais l'employé en `PARTI`, ne vérifie pas l'unicité (`UNIQUE(id_employe)` lèverait une erreur SQL brute).
7. `sanctionner()` : aucune conséquence (paie, rupture), et bloque si la réponse est absente même quand le délai est dépassé.
8. `repondreExplication()` : la RH saisit à la place de l'employé, sans pièce jointe ni détection du retard.
9. `dev/login-as` : emails absents du seed, pas de rôle `EMPLOYE`.
10. Seed : contrat 7 en doublon, variables HS ignorées par le calcul.

---

## 11. Ordre de travail conseillé

| Étape | Contenu | Dépend de |
|---|---|---|
| 0 | Appliquer le SQL (1.A, 1.B, 1.C), recréer la base, filtre admin + page admin, `Transitions`, `ParametreRh`, `NotificationService` | — |
| 1 | Espace employé minimal (notifications) | 0 |
| 2 | **Paie** : périodes, variables, HS, retards, puis refonte de `PaieService` + états | 0 |
| 3 | **Embauche directe** + `ContratPdfService` | 0, 2 (période) |
| 4 | **Évaluation / formation** : référentiel, évaluation, propositions, assignation, apport | 0, 1 |
| 5 | **Carrière** : service d'application, formulaire, propositions | 3, 4 |
| 6 | **Discipline** : dossier, décision, sanctions, réponses, espace employé | 1, 2 |
| 7 | **Rupture** : `RuptureService`, STC, checklist, PDF, lien avec la discipline | 2, 3, 6 |

Répartition possible à 4 : (Danie) paie + embauche ; (John) évaluation/formation + paramètres ; (Rindra) carrière + rupture ; (Tsiky) discipline + espace employé + admin.
