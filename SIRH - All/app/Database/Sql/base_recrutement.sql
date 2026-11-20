-- Schéma SQLite du module recrutement.
-- Les données de démonstration sont dans donnees.sql.

-- Base de données : recrutement et gestion RH
-- SGBD cible : SQLite3
-- Exécuter ce script dans une base vide.
-- Les documents de candidature sont stockés en BLOB ; prévoir des limites de taille côté application.

BEGIN;

CREATE TABLE departement (
    id_departement INTEGER PRIMARY KEY,
    nom VARCHAR(120) NOT NULL UNIQUE,
    description TEXT,
    actif BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE utilisateur (
    id_utilisateur INTEGER PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    mot_de_passe_hash TEXT NOT NULL,
    role VARCHAR(30) NOT NULL CHECK (role IN ('DEPARTEMENT', 'RH', 'DIRECTION', 'CANDIDAT', 'ADMINISTRATEUR', 'FINANCE')),
    actif BOOLEAN NOT NULL DEFAULT TRUE,
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE personne (
    id_personne INTEGER PRIMARY KEY,
    id_utilisateur BIGINT NOT NULL UNIQUE REFERENCES utilisateur(id_utilisateur),
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    telephone VARCHAR(30),
    adresse TEXT,
    date_naissance DATE
);

-- Profil candidat : un utilisateur peut avoir au plus un profil candidat.
CREATE TABLE candidat (
    id_candidat INTEGER PRIMARY KEY,
    id_personne BIGINT NOT NULL UNIQUE REFERENCES personne(id_personne),
    niveau_etude VARCHAR(120),
    experience TEXT
);

-- Association facultative des comptes du Département à un département.
CREATE TABLE utilisateur_departement (
    id_utilisateur BIGINT NOT NULL REFERENCES utilisateur(id_utilisateur),
    id_departement BIGINT NOT NULL REFERENCES departement(id_departement),
    PRIMARY KEY (id_utilisateur, id_departement)
);

CREATE TABLE poste (
    id_poste INTEGER PRIMARY KEY,
    id_departement BIGINT NOT NULL REFERENCES departement(id_departement),
    libelle VARCHAR(150) NOT NULL,
    description TEXT,
    statut VARCHAR(30) NOT NULL DEFAULT 'ACTIF'
        CHECK (statut IN ('ACTIF', 'INACTIF', 'FERME')),
    cree_par BIGINT REFERENCES utilisateur(id_utilisateur),
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE condition_contrat (
    id_condition INTEGER PRIMARY KEY,
    id_poste BIGINT NOT NULL REFERENCES poste(id_poste) ON DELETE CASCADE,
    libelle VARCHAR(180) NOT NULL,
    type_valeur VARCHAR(30) NOT NULL
        CHECK (type_valeur IN ('TEXTE', 'NOMBRE', 'MONTANT', 'DATE', 'BOOLEEN')),
    valeur_condition TEXT NOT NULL,
    ordre_affichage INTEGER NOT NULL DEFAULT 1 CHECK (ordre_affichage > 0),
    statut VARCHAR(30) NOT NULL DEFAULT 'CREER'
        CHECK (statut IN ('CREER', 'A_CONFIRMER', 'CONFIRMER', 'INACTIF')),
    modifie_par BIGINT REFERENCES utilisateur(id_utilisateur),
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP
);

CREATE TABLE demande_recrutement (
    id_demande INTEGER PRIMARY KEY,
    id_poste BIGINT NOT NULL REFERENCES poste(id_poste),
    id_demandeur BIGINT NOT NULL REFERENCES utilisateur(id_utilisateur),
    effectif_demande INTEGER NOT NULL CHECK (effectif_demande > 0),
    motif TEXT NOT NULL,
    salaire_par_personne NUMERIC(14,2) NOT NULL CHECK (salaire_par_personne >= 0),
    date_besoin DATE NOT NULL,
    statut VARCHAR(30) NOT NULL DEFAULT 'BROUILLON'
        CHECK (statut IN ('BROUILLON', 'EN_ATTENTE', 'VALIDE', 'REFUSE', 'REFUSEE', 'ANNULEE')),
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP
);

CREATE TABLE validation_demande (
    id_validation INTEGER PRIMARY KEY,
    id_demande BIGINT NOT NULL REFERENCES demande_recrutement(id_demande) ON DELETE CASCADE,
    id_validateur BIGINT NOT NULL REFERENCES utilisateur(id_utilisateur),
    type_validation VARCHAR(30) NOT NULL
        CHECK (type_validation IN ('RH', 'ADMINISTRATEUR', 'DIRECTION', 'FINANCE')),
    decision VARCHAR(30) NOT NULL
        CHECK (decision IN ('EN_ATTENTE', 'VALIDE', 'REFUSE', 'REFUSEE', 'REOUVERTURE')),
    commentaire TEXT,
    date_decision TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE historique_demande (
    id_historique INTEGER PRIMARY KEY,
    id_demande BIGINT NOT NULL REFERENCES demande_recrutement(id_demande) ON DELETE CASCADE,
    ancien_statut VARCHAR(30),
    nouveau_statut VARCHAR(30) NOT NULL,
    id_utilisateur BIGINT REFERENCES utilisateur(id_utilisateur),
    commentaire TEXT,
    date_action TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE offre_emploi (
    id_offre INTEGER PRIMARY KEY,
    id_demande BIGINT NOT NULL UNIQUE REFERENCES demande_recrutement(id_demande),
    titre VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    date_publication DATE,
    date_limite DATE,
    statut VARCHAR(30) NOT NULL DEFAULT 'BROUILLON'
        CHECK (statut IN ('BROUILLON', 'EN_ATTENTE', 'VALIDE', 'PUBLIEE', 'FERMEE', 'REFUSEE')),
    cree_par BIGINT REFERENCES utilisateur(id_utilisateur),
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (date_limite IS NULL OR date_publication IS NULL OR date_limite >= date_publication)
);

CREATE TABLE validation_offre (
    id_validation_offre INTEGER PRIMARY KEY,
    id_offre BIGINT NOT NULL REFERENCES offre_emploi(id_offre) ON DELETE CASCADE,
    id_validateur BIGINT NOT NULL REFERENCES utilisateur(id_utilisateur),
    decision VARCHAR(30) NOT NULL CHECK (decision IN ('VALIDE', 'REFUSEE', 'A_CORRIGER')),
    commentaire TEXT,
    date_decision TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE historique_poste (
    id_historique INTEGER PRIMARY KEY,
    id_poste BIGINT NOT NULL REFERENCES poste(id_poste) ON DELETE CASCADE,
    ancien_statut VARCHAR(30),
    nouveau_statut VARCHAR(30) NOT NULL,
    id_utilisateur BIGINT REFERENCES utilisateur(id_utilisateur),
    commentaire TEXT,
    date_action TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE historique_condition_contrat (
    id_historique INTEGER PRIMARY KEY,
    id_condition BIGINT NOT NULL REFERENCES condition_contrat(id_condition) ON DELETE CASCADE,
    ancienne_valeur TEXT,
    nouvelle_valeur TEXT,
    ancien_statut VARCHAR(30),
    nouveau_statut VARCHAR(30),
    id_utilisateur BIGINT REFERENCES utilisateur(id_utilisateur),
    commentaire TEXT,
    date_action TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE historique_offre_emploi (
    id_historique INTEGER PRIMARY KEY,
    id_offre BIGINT NOT NULL REFERENCES offre_emploi(id_offre) ON DELETE CASCADE,
    ancien_statut VARCHAR(30),
    nouveau_statut VARCHAR(30) NOT NULL,
    id_utilisateur BIGINT REFERENCES utilisateur(id_utilisateur),
    commentaire TEXT,
    date_action TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE candidature (
    id_candidature INTEGER PRIMARY KEY,
    id_offre BIGINT NOT NULL REFERENCES offre_emploi(id_offre),
    id_candidat BIGINT NOT NULL REFERENCES candidat(id_candidat),
    cv_nom VARCHAR(255) NOT NULL,
    cv_type_mime VARCHAR(120) NOT NULL,
    cv_donnees BLOB NOT NULL,
    lettre_nom VARCHAR(255) NOT NULL,
    lettre_type_mime VARCHAR(120) NOT NULL,
    lettre_donnees BLOB NOT NULL,
    cin_nom VARCHAR(255) NOT NULL,
    cin_type_mime VARCHAR(120) NOT NULL,
    cin_donnees BLOB NOT NULL,
    motivation TEXT,
    statut VARCHAR(30) NOT NULL DEFAULT 'RECUE'
        CHECK (statut IN ('RECUE', 'PRESELECTIONNEE', 'SELECTIONNEE', 'REFUSEE', 'RETENUE', 'ABANDONNEE')),
    date_candidature TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (id_offre, id_candidat)
);

CREATE TABLE historique_candidature (
    id_historique INTEGER PRIMARY KEY,
    id_candidature BIGINT NOT NULL REFERENCES candidature(id_candidature) ON DELETE CASCADE,
    ancien_statut VARCHAR(30),
    nouveau_statut VARCHAR(30) NOT NULL,
    id_utilisateur BIGINT REFERENCES utilisateur(id_utilisateur),
    commentaire TEXT,
    date_action TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE entretien (
    id_entretien INTEGER PRIMARY KEY,
    id_candidature BIGINT NOT NULL REFERENCES candidature(id_candidature) ON DELETE CASCADE,
    id_organisateur BIGINT NOT NULL REFERENCES utilisateur(id_utilisateur),
    date_entretien TIMESTAMP NOT NULL,
    lieu VARCHAR(255),
    notes TEXT,
    statut VARCHAR(30) NOT NULL DEFAULT 'PLANIFIE'
        CHECK (statut IN ('PLANIFIE', 'REALISE', 'ANNULE')),
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE historique_entretien (
    id_historique INTEGER PRIMARY KEY,
    id_entretien BIGINT NOT NULL REFERENCES entretien(id_entretien) ON DELETE CASCADE,
    ancien_statut VARCHAR(30),
    nouveau_statut VARCHAR(30) NOT NULL,
    id_utilisateur BIGINT REFERENCES utilisateur(id_utilisateur),
    commentaire TEXT,
    date_action TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE test_technique (
    id_test INTEGER PRIMARY KEY,
    id_candidature BIGINT NOT NULL REFERENCES candidature(id_candidature) ON DELETE CASCADE,
    id_organisateur BIGINT NOT NULL REFERENCES utilisateur(id_utilisateur),
    intitule VARCHAR(180) NOT NULL,
    description TEXT,
    date_test TIMESTAMP NOT NULL,
    statut VARCHAR(30) NOT NULL DEFAULT 'PLANIFIE'
        CHECK (statut IN ('PLANIFIE', 'REALISE', 'ANNULE')),
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE historique_test_technique (
    id_historique INTEGER PRIMARY KEY,
    id_test BIGINT NOT NULL REFERENCES test_technique(id_test) ON DELETE CASCADE,
    ancien_statut VARCHAR(30),
    nouveau_statut VARCHAR(30) NOT NULL,
    id_utilisateur BIGINT REFERENCES utilisateur(id_utilisateur),
    commentaire TEXT,
    date_action TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE evaluation_candidat (
    id_evaluation INTEGER PRIMARY KEY,
    id_candidature BIGINT NOT NULL REFERENCES candidature(id_candidature) ON DELETE CASCADE,
    id_evaluateur BIGINT NOT NULL REFERENCES utilisateur(id_utilisateur),
    id_entretien BIGINT REFERENCES entretien(id_entretien) ON DELETE CASCADE,
    id_test BIGINT REFERENCES test_technique(id_test) ON DELETE CASCADE,
    type_evaluation VARCHAR(30) NOT NULL
        CHECK (type_evaluation IN ('DOSSIER', 'ENTRETIEN', 'TEST_TECHNIQUE')),
    note NUMERIC(4,2) CHECK (note IS NULL OR (note >= 0 AND note <= 10)),
    commentaire TEXT,
    date_evaluation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (
        (type_evaluation = 'DOSSIER' AND id_entretien IS NULL AND id_test IS NULL)
        OR (type_evaluation = 'ENTRETIEN' AND id_entretien IS NOT NULL AND id_test IS NULL)
        OR (type_evaluation = 'TEST_TECHNIQUE' AND id_test IS NOT NULL AND id_entretien IS NULL)
    )
);

-- Proposition du candidat pour modifier une condition du contrat.
CREATE TABLE requete_condition_contrat (
    id_requete INTEGER PRIMARY KEY,
    id_candidature BIGINT NOT NULL REFERENCES candidature(id_candidature) ON DELETE CASCADE,
    id_condition BIGINT NOT NULL REFERENCES condition_contrat(id_condition),
    valeur_proposee TEXT NOT NULL,
    commentaire_candidat TEXT,
    statut VARCHAR(30) NOT NULL DEFAULT 'SOUMISE'
        CHECK (statut IN ('BROUILLON', 'SOUMISE', 'ACCEPTEE_RH', 'REFUSEE_RH', 'ACCEPTEE_DIRECTION', 'REFUSEE_DIRECTION')),
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP,
    UNIQUE (id_candidature, id_condition, date_creation)
);

CREATE TABLE historique_requete_condition (
    id_historique INTEGER PRIMARY KEY,
    id_requete BIGINT NOT NULL REFERENCES requete_condition_contrat(id_requete) ON DELETE CASCADE,
    ancien_statut VARCHAR(30),
    nouveau_statut VARCHAR(30) NOT NULL,
    id_utilisateur BIGINT REFERENCES utilisateur(id_utilisateur),
    commentaire TEXT,
    date_action TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE contrat (
    id_contrat INTEGER PRIMARY KEY,
    id_candidature BIGINT NOT NULL UNIQUE REFERENCES candidature(id_candidature),
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
        CHECK (statut IN ('BROUILLON', 'A_SIGNER', 'SIGNE', 'ANNULE')),
    pdf_nom VARCHAR(255),
    pdf_type_mime VARCHAR(120),
    pdf_donnees BLOB,
    date_signature TIMESTAMP,
    CHECK (date_fin IS NULL OR date_fin >= date_debut)
);

-- Copie figée des conditions définitives intégrées au contrat signé.
CREATE TABLE contrat_condition (
    id_contrat_condition INTEGER PRIMARY KEY,
    id_contrat BIGINT NOT NULL REFERENCES contrat(id_contrat) ON DELETE CASCADE,
    libelle VARCHAR(180) NOT NULL,
    type_valeur VARCHAR(30) NOT NULL,
    valeur_finale TEXT NOT NULL,
    ordre_affichage INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE employe (
    id_employe INTEGER PRIMARY KEY,
    id_contrat BIGINT NOT NULL UNIQUE REFERENCES contrat(id_contrat),
    id_personne BIGINT NOT NULL REFERENCES personne(id_personne),
    matricule VARCHAR(80) NOT NULL UNIQUE,
    date_embauche DATE NOT NULL,
    statut VARCHAR(30) NOT NULL DEFAULT 'ACTIF'
        CHECK (statut IN ('ACTIF', 'SUSPENDU', 'PARTI'))
);

CREATE TABLE affectation (
    id_affectation INTEGER PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employe(id_employe) ON DELETE CASCADE,
    id_departement BIGINT NOT NULL REFERENCES departement(id_departement),
    date_debut DATE NOT NULL,
    date_fin DATE,
    fonction VARCHAR(150),
    CHECK (date_fin IS NULL OR date_fin >= date_debut)
);

CREATE TABLE historique_contrat (
    id_historique INTEGER PRIMARY KEY,
    id_contrat BIGINT NOT NULL REFERENCES contrat(id_contrat) ON DELETE CASCADE,
    ancien_statut VARCHAR(30),
    nouveau_statut VARCHAR(30) NOT NULL,
    id_utilisateur BIGINT REFERENCES utilisateur(id_utilisateur),
    commentaire TEXT,
    date_action TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE historique_employe (
    id_historique INTEGER PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employe(id_employe) ON DELETE CASCADE,
    ancien_statut VARCHAR(30),
    nouveau_statut VARCHAR(30) NOT NULL,
    id_utilisateur BIGINT REFERENCES utilisateur(id_utilisateur),
    commentaire TEXT,
    date_action TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE historique_salaire (
    id_historique INTEGER PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employe(id_employe) ON DELETE CASCADE,
    ancien_salaire NUMERIC(14,2),
    nouveau_salaire NUMERIC(14,2) NOT NULL CHECK (nouveau_salaire >= 0),
    periodicite_salaire VARCHAR(30),
    motif TEXT,
    id_utilisateur BIGINT REFERENCES utilisateur(id_utilisateur),
    date_effet DATE NOT NULL,
    date_action TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE document_employe (
    id_document INTEGER PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employe(id_employe) ON DELETE CASCADE,
    type_document VARCHAR(50) NOT NULL,
    nom_fichier VARCHAR(255) NOT NULL,
    type_mime VARCHAR(120) NOT NULL,
    donnees BLOB NOT NULL,
    date_expiration DATE,
    commentaire TEXT,
    id_utilisateur BIGINT REFERENCES utilisateur(id_utilisateur),
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP
);

CREATE TABLE journal_acces (
    id_journal INTEGER PRIMARY KEY,
    id_utilisateur BIGINT REFERENCES utilisateur(id_utilisateur),
    id_employe BIGINT REFERENCES employe(id_employe) ON DELETE SET NULL,
    action VARCHAR(30) NOT NULL CHECK (action IN ('CONSULTATION', 'CREATION', 'MODIFICATION', 'TELECHARGEMENT')),
    ressource VARCHAR(80) NOT NULL,
    adresse_ip VARCHAR(45),
    details TEXT,
    date_action TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE notification (
    id_notification INTEGER PRIMARY KEY,
    id_utilisateur BIGINT NOT NULL REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE,
    titre VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    lien VARCHAR(500),
    lue BOOLEAN NOT NULL DEFAULT FALSE,
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_lecture TIMESTAMP
);

CREATE TABLE historique_formulaire_reponse (
    id_historique INTEGER PRIMARY KEY,
    id_utilisateur BIGINT REFERENCES utilisateur(id_utilisateur),
    type_formulaire VARCHAR(80) NOT NULL,
    id_enregistrement BIGINT NOT NULL,
    donnees TEXT NOT NULL,
    action VARCHAR(30) NOT NULL CHECK (action IN ('CREATION', 'MODIFICATION', 'SUPPRESSION', 'SOUMISSION')),
    date_action TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Index utiles pour les listes et les jointures les plus fréquentes.
CREATE INDEX idx_poste_departement ON poste(id_departement);
CREATE INDEX idx_demande_statut ON demande_recrutement(statut);
CREATE INDEX idx_demande_poste ON demande_recrutement(id_poste);
CREATE INDEX idx_offre_statut_dates ON offre_emploi(statut, date_publication, date_limite);
CREATE INDEX idx_candidature_offre_statut ON candidature(id_offre, statut);
CREATE INDEX idx_candidature_candidat ON candidature(id_candidat);
CREATE INDEX idx_entretien_candidature_date ON entretien(id_candidature, date_entretien);
CREATE INDEX idx_test_candidature_date ON test_technique(id_candidature, date_test);
CREATE INDEX idx_notification_utilisateur_lue ON notification(id_utilisateur, lue);
CREATE INDEX idx_requete_statut ON requete_condition_contrat(statut);
CREATE INDEX idx_historique_salaire_employe_date ON historique_salaire(id_employe, date_effet);
CREATE INDEX idx_document_employe_expiration ON document_employe(id_employe, date_expiration);
CREATE INDEX idx_journal_acces_employe_date ON journal_acces(id_employe, date_action);

-- Bloc 6 : temps de travail et pointage
CREATE TABLE regime_travail (
    id_regime INTEGER PRIMARY KEY,
    libelle VARCHAR(120) NOT NULL UNIQUE,
    heures_hebdomadaires NUMERIC(5,2) NOT NULL DEFAULT 40 CHECK (heures_hebdomadaires > 0),
    actif BOOLEAN NOT NULL DEFAULT TRUE
);
CREATE TABLE horaire (
    id_horaire INTEGER PRIMARY KEY,
    id_regime BIGINT REFERENCES regime_travail(id_regime),
    libelle VARCHAR(120) NOT NULL,
    heure_debut TIME NOT NULL,
    heure_fin TIME NOT NULL,
    pause_minutes INTEGER NOT NULL DEFAULT 0 CHECK (pause_minutes >= 0),
    actif BOOLEAN NOT NULL DEFAULT TRUE
);
CREATE TABLE planning (
    id_planning INTEGER PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employe(id_employe) ON DELETE CASCADE,
    id_horaire BIGINT NOT NULL REFERENCES horaire(id_horaire),
    date_planning DATE NOT NULL,
    statut VARCHAR(20) NOT NULL DEFAULT 'PLANIFIE'
        CHECK (statut IN ('PLANIFIE', 'VALIDE', 'ANNULE')),
    UNIQUE (id_employe, date_planning)
);
CREATE TABLE pointage (
    id_pointage INTEGER PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employe(id_employe) ON DELETE CASCADE,
    date_pointage DATE NOT NULL,
    heure_entree TIMESTAMP,
    heure_sortie TIMESTAMP,
    source VARCHAR(20) NOT NULL DEFAULT 'MANUEL'
        CHECK (source IN ('MANUEL', 'BADGE', 'IMPORT')),
    statut VARCHAR(20) NOT NULL DEFAULT 'OUVERT'
        CHECK (statut IN ('OUVERT', 'CLOTURE', 'VALIDE')),
    commentaire TEXT,
    UNIQUE (id_employe, date_pointage),
    CHECK (heure_sortie IS NULL OR heure_entree IS NULL OR heure_sortie >= heure_entree)
);
CREATE TABLE retard (
    id_retard INTEGER PRIMARY KEY,
    id_pointage BIGINT NOT NULL UNIQUE REFERENCES pointage(id_pointage) ON DELETE CASCADE,
    minutes INTEGER NOT NULL CHECK (minutes > 0),
    motif TEXT,
    justifie BOOLEAN NOT NULL DEFAULT FALSE,
    statut VARCHAR(20) NOT NULL DEFAULT 'A_TRAITER'
        CHECK (statut IN ('A_TRAITER', 'JUSTIFIE', 'VALIDE'))
);
CREATE TABLE heure_supplementaire (
    id_heure_supplementaire INTEGER PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employe(id_employe) ON DELETE CASCADE,
    date_travail DATE NOT NULL,
    heures NUMERIC(5,2) NOT NULL CHECK (heures > 0),
    motif TEXT,
    statut VARCHAR(20) NOT NULL DEFAULT 'A_VALIDER'
        CHECK (statut IN ('A_VALIDER', 'VALIDE', 'REFUSEE')),
    id_validateur BIGINT REFERENCES utilisateur(id_utilisateur),
    UNIQUE (id_employe, date_travail)
);
CREATE TABLE temps_travaille (
    id_temps INTEGER PRIMARY KEY,
    id_pointage BIGINT NOT NULL UNIQUE REFERENCES pointage(id_pointage) ON DELETE CASCADE,
    minutes_travaillees INTEGER NOT NULL DEFAULT 0 CHECK (minutes_travaillees >= 0),
    minutes_theoriques INTEGER NOT NULL DEFAULT 0 CHECK (minutes_theoriques >= 0),
    ecart_minutes INTEGER NOT NULL DEFAULT 0,
    calcule_le TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE anomalie_pointage (
    id_anomalie INTEGER PRIMARY KEY,
    id_pointage BIGINT REFERENCES pointage(id_pointage) ON DELETE CASCADE,
    id_employe BIGINT NOT NULL REFERENCES employe(id_employe) ON DELETE CASCADE,
    date_anomalie DATE NOT NULL,
    type_anomalie VARCHAR(30) NOT NULL
        CHECK (type_anomalie IN ('ABSENCE', 'ENTREE_MANQUANTE', 'SORTIE_MANQUANTE', 'RETARD', 'DEPASSEMENT')),
    description TEXT,
    statut VARCHAR(20) NOT NULL DEFAULT 'OUVERTE',
    date_resolution TIMESTAMP
);
CREATE INDEX idx_pointage_employe_date ON pointage(id_employe, date_pointage);
CREATE INDEX idx_planning_date ON planning(date_planning);

-- Bloc 7 : congés et absences.
CREATE TABLE type_conge (
    id_type_conge INTEGER PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    libelle VARCHAR(120) NOT NULL,
    description TEXT,
    jours_annuels NUMERIC(6,2) NOT NULL DEFAULT 0 CHECK (jours_annuels >= 0),
    justificatif_obligatoire BOOLEAN NOT NULL DEFAULT FALSE,
    actif BOOLEAN NOT NULL DEFAULT TRUE
);
CREATE TABLE droit_conge (
    id_droit_conge INTEGER PRIMARY KEY,
    id_type_conge BIGINT NOT NULL REFERENCES type_conge(id_type_conge),
    code_role VARCHAR(30),
    anciennete_mois INTEGER NOT NULL DEFAULT 0 CHECK (anciennete_mois >= 0),
    jours_annuels NUMERIC(6,2) NOT NULL CHECK (jours_annuels >= 0),
    UNIQUE (id_type_conge, code_role, anciennete_mois)
);
CREATE TABLE solde_conge (
    id_solde_conge INTEGER PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employe(id_employe) ON DELETE CASCADE,
    id_type_conge BIGINT NOT NULL REFERENCES type_conge(id_type_conge),
    annee INTEGER NOT NULL,
    acquis NUMERIC(6,2) NOT NULL DEFAULT 0 CHECK (acquis >= 0),
    pris NUMERIC(6,2) NOT NULL DEFAULT 0 CHECK (pris >= 0),
    ajuste NUMERIC(6,2) NOT NULL DEFAULT 0,
    UNIQUE (id_employe, id_type_conge, annee)
);
CREATE TABLE demande_conge (
    id_demande_conge INTEGER PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employe(id_employe) ON DELETE CASCADE,
    id_type_conge BIGINT NOT NULL REFERENCES type_conge(id_type_conge),
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    nombre_jours NUMERIC(6,2) NOT NULL CHECK (nombre_jours > 0),
    motif TEXT,
    statut VARCHAR(20) NOT NULL DEFAULT 'EN_ATTENTE' CHECK (statut IN ('EN_ATTENTE', 'VALIDE', 'REFUSE', 'ANNULE')),
    id_validateur BIGINT REFERENCES utilisateur(id_utilisateur),
    commentaire_validation TEXT,
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_decision TIMESTAMP,
    CHECK (date_fin >= date_debut)
);
CREATE TABLE justificatif_conge (
    id_justificatif INTEGER PRIMARY KEY,
    id_demande_conge BIGINT NOT NULL REFERENCES demande_conge(id_demande_conge) ON DELETE CASCADE,
    nom_fichier VARCHAR(255) NOT NULL, type_mime VARCHAR(120) NOT NULL, donnees BLOB NOT NULL,
    date_depot TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE absence (
    id_absence INTEGER PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employe(id_employe) ON DELETE CASCADE,
    date_debut DATE NOT NULL, date_fin DATE NOT NULL,
    type_absence VARCHAR(30) NOT NULL DEFAULT 'ABSENCE', motif TEXT,
    justifiee BOOLEAN NOT NULL DEFAULT FALSE,
    id_justificatif BIGINT REFERENCES justificatif_conge(id_justificatif),
    cree_par BIGINT REFERENCES utilisateur(id_utilisateur),
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (date_fin >= date_debut)
);
CREATE INDEX idx_demande_conge_employe_dates ON demande_conge(id_employe, date_debut, date_fin);
CREATE INDEX idx_demande_conge_statut ON demande_conge(statut);
CREATE INDEX idx_solde_conge_employe_annee ON solde_conge(id_employe, annee);
CREATE INDEX idx_absence_employe_dates ON absence(id_employe, date_debut, date_fin);
INSERT OR IGNORE INTO type_conge (code, libelle, jours_annuels) VALUES
    ('ANNUEL', 'Congé annuel', 30), ('MALADIE', 'Congé maladie', 0), ('SANS_SOLDE', 'Congé sans solde', 0);

-- Blocs 8 à 12
CREATE TABLE parametre_paie (
    id_parametre INTEGER PRIMARY KEY,
    code VARCHAR(60) NOT NULL,
    valeur NUMERIC(12,4) NOT NULL,
    secteur VARCHAR(20) NOT NULL DEFAULT 'NON_AGRICOLE',
    date_effet DATE NOT NULL,
    date_fin DATE,
    UNIQUE(code, secteur, date_effet)
);
CREATE TABLE rubrique_paie (
    id_rubrique INTEGER PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    libelle VARCHAR(120) NOT NULL,
    nature VARCHAR(20) NOT NULL CHECK(nature IN ('GAIN','RETENUE','PATRONAL')),
    imposable BOOLEAN NOT NULL DEFAULT TRUE,
    actif BOOLEAN NOT NULL DEFAULT TRUE
);
CREATE TABLE periode_paie (
    id_periode INTEGER PRIMARY KEY,
    mois INTEGER NOT NULL CHECK(mois BETWEEN 1 AND 12),
    annee INTEGER NOT NULL CHECK(annee >= 2000),
    date_paiement DATE,
    statut VARCHAR(20) NOT NULL DEFAULT 'OUVERT'
        CHECK(statut IN ('OUVERT','CALCUL','VALIDE','PAIE','CLOTURE','ANNULE')),
    UNIQUE(mois, annee)
);
CREATE TABLE variable_paie (
    id_variable INTEGER PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employe(id_employe),
    id_periode BIGINT NOT NULL REFERENCES periode_paie(id_periode),
    id_rubrique BIGINT NOT NULL REFERENCES rubrique_paie(id_rubrique),
    nombre NUMERIC(10,2) NOT NULL DEFAULT 0,
    montant NUMERIC(14,2) NOT NULL DEFAULT 0,
    motif TEXT,
    UNIQUE(id_employe,id_periode,id_rubrique)
);
CREATE TABLE bulletin_paie (
    id_bulletin INTEGER PRIMARY KEY,
    id_periode BIGINT NOT NULL REFERENCES periode_paie(id_periode),
    id_employe BIGINT NOT NULL REFERENCES employe(id_employe),
    salaire_base NUMERIC(14,2) NOT NULL,
    base_mois NUMERIC(14,2) NOT NULL,
    retenue_absence NUMERIC(14,2) NOT NULL DEFAULT 0,
    indemnites NUMERIC(14,2) NOT NULL DEFAULT 0,
    majorations NUMERIC(14,2) NOT NULL DEFAULT 0,
    salaire_brut NUMERIC(14,2) NOT NULL,
    cnaps_salarial NUMERIC(14,2) NOT NULL,
    cnaps_patronal NUMERIC(14,2) NOT NULL,
    ostie_salarial NUMERIC(14,2) NOT NULL,
    ostie_patronal NUMERIC(14,2) NOT NULL,
    net_imposable NUMERIC(14,2) NOT NULL,
    irsa NUMERIC(14,2) NOT NULL,
    total_retenues NUMERIC(14,2) NOT NULL,
    net_a_payer NUMERIC(14,2) NOT NULL,
    statut VARCHAR(20) NOT NULL DEFAULT 'BROUILLON'
        CHECK(statut IN ('BROUILLON','VALIDE','PAIE','ANNULE')),
    pdf_nom VARCHAR(255),
    pdf_type_mime VARCHAR(120),
    pdf_donnees BLOB,
    date_calcul TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(id_periode,id_employe)
);
CREATE TABLE ligne_bulletin (
    id_ligne INTEGER PRIMARY KEY,
    id_bulletin BIGINT NOT NULL REFERENCES bulletin_paie(id_bulletin) ON DELETE CASCADE,
    code VARCHAR(30) NOT NULL,
    designation VARCHAR(150) NOT NULL,
    base_nombre NUMERIC(12,2),
    taux NUMERIC(12,4),
    gain NUMERIC(14,2) NOT NULL DEFAULT 0,
    retenue NUMERIC(14,2) NOT NULL DEFAULT 0,
    ordre_affichage INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE competence (
    id_competence INTEGER PRIMARY KEY,
    code VARCHAR(40) NOT NULL UNIQUE,
    libelle VARCHAR(150) NOT NULL,
    description TEXT,
    actif BOOLEAN NOT NULL DEFAULT TRUE
);
CREATE TABLE poste_competence (
    id_poste_competence INTEGER PRIMARY KEY,
    id_poste BIGINT NOT NULL REFERENCES poste(id_poste) ON DELETE CASCADE,
    id_competence BIGINT NOT NULL REFERENCES competence(id_competence),
    niveau_attendu INTEGER NOT NULL CHECK(niveau_attendu BETWEEN 1 AND 5),
    UNIQUE(id_poste,id_competence)
);
CREATE TABLE employe_competence (
    id_employe_competence INTEGER PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employe(id_employe) ON DELETE CASCADE,
    id_competence BIGINT NOT NULL REFERENCES competence(id_competence),
    niveau_acquis INTEGER NOT NULL CHECK(niveau_acquis BETWEEN 1 AND 5),
    date_evaluation DATE NOT NULL,
    id_evaluateur BIGINT REFERENCES utilisateur(id_utilisateur),
    UNIQUE(id_employe,id_competence,date_evaluation)
);
CREATE TABLE objectif (
    id_objectif INTEGER PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employe(id_employe) ON DELETE CASCADE,
    titre VARCHAR(150) NOT NULL,
    description TEXT,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    resultat TEXT,
    statut VARCHAR(20) NOT NULL DEFAULT 'OUVERT'
        CHECK(statut IN ('OUVERT','EN_COURS','ATTEINT','NON_ATTEINT','ANNULE')),
    CHECK(date_fin >= date_debut)
);
CREATE TABLE evaluation_performance (
    id_evaluation_performance INTEGER PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employe(id_employe) ON DELETE CASCADE,
    id_evaluateur BIGINT NOT NULL REFERENCES utilisateur(id_utilisateur),
    periode VARCHAR(20) NOT NULL,
    note_globale NUMERIC(4,2) CHECK(note_globale BETWEEN 0 AND 20),
    commentaire TEXT,
    statut VARCHAR(20) NOT NULL DEFAULT 'BROUILLON'
        CHECK(statut IN ('BROUILLON','VALIDE','ARCHIVE')),
    date_evaluation DATE NOT NULL
);
CREATE TABLE formation (
    id_formation INTEGER PRIMARY KEY,
    titre VARCHAR(180) NOT NULL,
    description TEXT,
    id_competence BIGINT REFERENCES competence(id_competence),
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    prestataire VARCHAR(180),
    cout NUMERIC(14,2) NOT NULL DEFAULT 0,
    statut VARCHAR(20) NOT NULL DEFAULT 'PLANIFIEE'
        CHECK(statut IN ('PLANIFIEE','EN_COURS','TERMINEE','ANNULEE')),
    CHECK(date_fin >= date_debut)
);
CREATE TABLE formation_participant (
    id_participant INTEGER PRIMARY KEY,
    id_formation BIGINT NOT NULL REFERENCES formation(id_formation) ON DELETE CASCADE,
    id_employe BIGINT NOT NULL REFERENCES employe(id_employe) ON DELETE CASCADE,
    statut VARCHAR(20) NOT NULL DEFAULT 'INSCRIT'
        CHECK(statut IN ('INSCRIT','PRESENT','ABANDON','TERMINE')),
    niveau_avant INTEGER CHECK(niveau_avant BETWEEN 1 AND 5),
    niveau_apres INTEGER CHECK(niveau_apres BETWEEN 1 AND 5),
    UNIQUE(id_formation,id_employe)
);

CREATE TABLE mouvement_carriere (
    id_mouvement INTEGER PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employe(id_employe) ON DELETE CASCADE,
    type_mouvement VARCHAR(30) NOT NULL
        CHECK(type_mouvement IN ('PROMOTION','MUTATION','CHANGEMENT_STATUT','REVISION_SALAIRE','AFFECTATION')),
    ancien_poste VARCHAR(150),
    nouveau_poste VARCHAR(150),
    ancien_salaire NUMERIC(14,2),
    nouveau_salaire NUMERIC(14,2),
    id_departement BIGINT REFERENCES departement(id_departement),
    date_effet DATE NOT NULL,
    date_fin DATE,
    motif TEXT,
    id_auteur BIGINT REFERENCES utilisateur(id_utilisateur),
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_mouvement_employe_date ON mouvement_carriere(id_employe,date_effet);

CREATE TABLE incident (
    id_incident INTEGER PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employe(id_employe) ON DELETE CASCADE,
    date_incident DATE NOT NULL,
    objet VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    statut VARCHAR(20) NOT NULL DEFAULT 'SIGNALE'
        CHECK(statut IN ('SIGNALE','EXPLICATIONS','REPONSE','COMMISSION','DECIDE','SANCTION','CLOTURE')),
    id_signaleur BIGINT REFERENCES utilisateur(id_utilisateur),
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE demande_explication (
    id_demande_explication INTEGER PRIMARY KEY,
    id_incident BIGINT NOT NULL UNIQUE REFERENCES incident(id_incident) ON DELETE CASCADE,
    date_demande DATE NOT NULL,
    date_limite DATE NOT NULL,
    texte TEXT NOT NULL,
    date_notification TIMESTAMP,
    CHECK(date_limite >= date_demande)
);
CREATE TABLE reponse_explication (
    id_reponse INTEGER PRIMARY KEY,
    id_incident BIGINT NOT NULL UNIQUE REFERENCES incident(id_incident) ON DELETE CASCADE,
    date_reponse DATE NOT NULL,
    texte TEXT NOT NULL,
    piece_jointe_nom VARCHAR(255),
    piece_jointe_mime VARCHAR(120),
    piece_jointe_donnees BLOB
);
CREATE TABLE sanction (
    id_sanction INTEGER PRIMARY KEY,
    id_incident BIGINT NOT NULL UNIQUE REFERENCES incident(id_incident) ON DELETE CASCADE,
    type_sanction VARCHAR(40) NOT NULL
        CHECK(type_sanction IN ('AVERTISSEMENT','BLAME','MISE_A_PIED','LICENCEMENT','AUTRE')),
    motif TEXT NOT NULL,
    date_decision DATE NOT NULL,
    date_effet DATE NOT NULL,
    id_decideur BIGINT REFERENCES utilisateur(id_utilisateur),
    statut VARCHAR(20) NOT NULL DEFAULT 'NOTIFIEE'
        CHECK(statut IN ('NOTIFIEE','CONTESTEE','EXECUTOIRE','ANNULEE')),
    piece_notification BLOB
);

CREATE TABLE rupture_contrat (
    id_rupture INTEGER PRIMARY KEY,
    id_employe BIGINT NOT NULL UNIQUE REFERENCES employe(id_employe),
    motif VARCHAR(40) NOT NULL
        CHECK(motif IN ('DEMISSION','LICENCIEMENT_ECONOMIQUE','LICENCIEMENT_FAUTE','FIN_CDD','RETRAITE','RUPTURE_CONVENTIONNELLE','AUTRE')),
    date_notification DATE NOT NULL,
    date_sortie DATE NOT NULL,
    duree_preavis_jours INTEGER NOT NULL DEFAULT 0,
    motif_detail TEXT,
    statut VARCHAR(20) NOT NULL DEFAULT 'OUVERT'
        CHECK(statut IN ('OUVERT','EN_COURS','VALIDEE','CLOTURE','ANNULEE')),
    id_auteur BIGINT REFERENCES utilisateur(id_utilisateur),
    CHECK(date_sortie >= date_notification)
);
CREATE TABLE solde_tout_compte (
    id_stc INTEGER PRIMARY KEY,
    id_rupture BIGINT NOT NULL UNIQUE REFERENCES rupture_contrat(id_rupture),
    dernier_salaire NUMERIC(14,2) NOT NULL DEFAULT 0,
    salaire_proratise NUMERIC(14,2) NOT NULL DEFAULT 0,
    indemnite_conges NUMERIC(14,2) NOT NULL DEFAULT 0,
    indemnite_rupture NUMERIC(14,2) NOT NULL DEFAULT 0,
    avances NUMERIC(14,2) NOT NULL DEFAULT 0,
    retenues NUMERIC(14,2) NOT NULL DEFAULT 0,
    montant_net NUMERIC(14,2) NOT NULL DEFAULT 0,
    statut VARCHAR(20) NOT NULL DEFAULT 'BROUILLON'
        CHECK(statut IN ('BROUILLON','VALIDE','PAIE','ANNULE')),
    date_calcul TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE checklist_offboarding (
    id_checklist INTEGER PRIMARY KEY,
    id_rupture BIGINT NOT NULL REFERENCES rupture_contrat(id_rupture) ON DELETE CASCADE,
    element VARCHAR(100) NOT NULL,
    valeur_statut VARCHAR(30) NOT NULL DEFAULT 'A_FAIRE'
        CHECK(valeur_statut IN ('A_FAIRE','EN_COURS','TERMINE','NON_APPLICABLE')),
    date_echeance DATE,
    date_realisation TIMESTAMP,
    commentaire TEXT,
    UNIQUE(id_rupture,element)
);
CREATE TABLE attestation_travail (
    id_attestation INTEGER PRIMARY KEY,
    id_rupture BIGINT NOT NULL REFERENCES rupture_contrat(id_rupture) ON DELETE CASCADE,
    type_attestation VARCHAR(40) NOT NULL
        CHECK(type_attestation IN ('CERTIFICAT_TRAVAIL','ATTESTATION_CNAPS','ATTESTATION_OSTIE','AUTRE')),
    reference VARCHAR(80) NOT NULL UNIQUE,
    pdf_nom VARCHAR(255),
    pdf_type_mime VARCHAR(120),
    pdf_donnees BLOB,
    date_emission TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE audit_rh (
    id_audit INTEGER PRIMARY KEY,
    id_utilisateur BIGINT REFERENCES utilisateur(id_utilisateur),
    action VARCHAR(20) NOT NULL,
    objet VARCHAR(80) NOT NULL,
    objet_id INTEGER,
    ancienne_valeur TEXT,
    nouvelle_valeur TEXT,
    motif TEXT,
    adresse_ip VARCHAR(45),
    date_action TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

COMMIT;
