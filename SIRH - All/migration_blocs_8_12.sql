-- Migration blocs 8 à 12 : à passer APRÈS base_recrutement.sql et AVANT les données.
PRAGMA foreign_keys = ON;
BEGIN;

ALTER TABLE employe ADD COLUMN id_poste   BIGINT REFERENCES poste(id_poste);
ALTER TABLE employe ADD COLUMN id_regime  BIGINT REFERENCES regime_travail(id_regime);

-- ===== PAIE =====
ALTER TABLE retard ADD COLUMN automatique BOOLEAN NOT NULL DEFAULT 0;
ALTER TABLE retard ADD COLUMN date_justification TIMESTAMP;
ALTER TABLE retard ADD COLUMN id_valideur BIGINT REFERENCES utilisateur(id_utilisateur);
ALTER TABLE retard ADD COLUMN justificatif TEXT;

ALTER TABLE rubrique_paie ADD COLUMN categorie VARCHAR(20) NOT NULL DEFAULT 'AUTRE';
UPDATE rubrique_paie SET categorie = CASE code
  WHEN 'INDEMNITE' THEN 'INDEMNITE' WHEN 'INDEMNITE_NON_IMPOSABLE' THEN 'INDEMNITE'
  WHEN 'PRIME' THEN 'PRIME' WHEN 'RAPPEL' THEN 'RAPPEL' WHEN 'RETENUE' THEN 'AVANCE'
  WHEN 'HS_30' THEN 'HS' WHEN 'HS_50' THEN 'HS' WHEN 'DIMANCHE_NUIT' THEN 'HS'
  ELSE 'AUTRE' END;
INSERT OR IGNORE INTO rubrique_paie(code,libelle,nature,imposable,categorie) VALUES
 ('INDEMNITE','Indemnité','GAIN',1,'INDEMNITE'),
 ('INDEMNITE_NON_IMPOSABLE','Indemnité non imposable','GAIN',0,'INDEMNITE'),
 ('PRIME','Prime exceptionnelle','GAIN',1,'PRIME'),
 ('RAPPEL','Rappel de salaire','GAIN',1,'RAPPEL'),
 ('RETENUE','Avance ou acompte','RETENUE',0,'AVANCE'),
 ('INDEMNITE_LOGEMENT','Indemnité de logement','GAIN',1,'INDEMNITE'),
 ('INDEMNITE_TRANSPORT','Indemnité de transport','GAIN',1,'INDEMNITE'),
 ('INDEMNITE_REPRESENTATION','Indemnité de représentation','GAIN',1,'INDEMNITE'),
 ('PRIME_ANCIENNETE','Prime d''ancienneté','GAIN',1,'PRIME');

ALTER TABLE bulletin_paie ADD COLUMN jours_travailles NUMERIC(5,2) NOT NULL DEFAULT 30;
ALTER TABLE bulletin_paie ADD COLUMN retenue_prorata NUMERIC(14,2) NOT NULL DEFAULT 0;
ALTER TABLE bulletin_paie ADD COLUMN retenue_retard NUMERIC(14,2) NOT NULL DEFAULT 0;
ALTER TABLE bulletin_paie ADD COLUMN retenue_mise_a_pied NUMERIC(14,2) NOT NULL DEFAULT 0;
ALTER TABLE bulletin_paie ADD COLUMN indemnites_non_imposables NUMERIC(14,2) NOT NULL DEFAULT 0;
ALTER TABLE bulletin_paie ADD COLUMN autres_retenues NUMERIC(14,2) NOT NULL DEFAULT 0;
ALTER TABLE bulletin_paie ADD COLUMN inclus_stc BOOLEAN NOT NULL DEFAULT 0;

-- Minimum de perception IRSA (paramètre, pas de valeur en dur dans le code)
INSERT OR IGNORE INTO parametre_paie(code,valeur,secteur,date_effet) VALUES
 ('IRSA_MINIMUM',3000,'NON_AGRICOLE','2000-01-01'),
 ('IRSA_MINIMUM',3000,'AGRICOLE','2000-01-01');

-- ===== PARAMÈTRES RH =====
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

CREATE TABLE ecart_competence (
    id_ecart INTEGER PRIMARY KEY,
    id_evaluation_performance BIGINT NOT NULL REFERENCES evaluation_performance(id_evaluation_performance) ON DELETE CASCADE,
    id_employe BIGINT NOT NULL REFERENCES employe(id_employe) ON DELETE CASCADE,
    id_competence BIGINT NOT NULL REFERENCES competence(id_competence),
    niveau_attendu INTEGER NOT NULL,
    niveau_acquis INTEGER NOT NULL,
    ecart INTEGER NOT NULL,
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
CREATE VIEW v_apport_formation AS
SELECT pf.id_proposition_formation, pf.id_employe, pf.id_competence, pf.id_formation,
       e1.ecart AS ecart_avant, e2.ecart AS ecart_apres, (e1.ecart - e2.ecart) AS apport
FROM proposition_formation pf
JOIN ecart_competence e1 ON e1.id_ecart = pf.id_ecart
JOIN evaluation_performance ev ON ev.id_employe = pf.id_employe
     AND ev.type_evaluation = 'POST_FORMATION' AND ev.id_formation = pf.id_formation
JOIN ecart_competence e2 ON e2.id_evaluation_performance = ev.id_evaluation_performance
     AND e2.id_competence = pf.id_competence;

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
ALTER TABLE incident ADD COLUMN decision_finale VARCHAR(20)
    CHECK(decision_finale IS NULL OR decision_finale IN ('SANCTION','CLASSE_SANS_SUITE'));
ALTER TABLE incident ADD COLUMN date_commission DATE;
ALTER TABLE incident ADD COLUMN commentaire_commission TEXT;
ALTER TABLE reponse_explication ADD COLUMN en_retard BOOLEAN NOT NULL DEFAULT 0;
ALTER TABLE reponse_explication ADD COLUMN id_auteur BIGINT REFERENCES utilisateur(id_utilisateur);
ALTER TABLE sanction ADD COLUMN nb_jours INTEGER NOT NULL DEFAULT 0 CHECK(nb_jours >= 0);
ALTER TABLE sanction ADD COLUMN commentaire_contestation TEXT;

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
