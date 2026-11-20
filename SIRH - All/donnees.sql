BEGIN;
-- Données minimales de démonstration
INSERT INTO departement (id_departement, nom, description, actif) VALUES
(1, 'Informatique', 'Département informatique', TRUE)
ON CONFLICT(id_departement) DO NOTHING;

INSERT INTO utilisateur (id_utilisateur, email, mot_de_passe_hash, role, actif) VALUES
(1, 'direction@test.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DIRECTION', TRUE),
(2, 'candidat@test.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'CANDIDAT', TRUE),
(3, 'rh@test.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'RH', TRUE),
(4, 'dept@test.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DEPARTEMENT', TRUE)
ON CONFLICT(id_utilisateur) DO NOTHING;

INSERT INTO personne (id_personne, id_utilisateur, nom, prenom, telephone, adresse) VALUES
(1, 2, 'Rakoto', 'Jean', '0340000000', 'Antananarivo')
ON CONFLICT(id_personne) DO NOTHING;
INSERT INTO candidat (id_candidat, id_personne, niveau_etude, experience) VALUES
(1, 1, 'Bac+5', '3 ans en développement web')
ON CONFLICT(id_candidat) DO NOTHING;
INSERT INTO poste (id_poste, id_departement, libelle, description, statut, cree_par) VALUES
(1, 1, 'Développeur Fullstack', 'Poste de test', 'ACTIF', 4)
ON CONFLICT(id_poste) DO NOTHING;
INSERT INTO condition_contrat (id_condition, id_poste, libelle, type_valeur, valeur_condition, ordre_affichage, statut) VALUES
(1, 1, 'Salaire mensuel (Ar)', 'MONTANT', '800000', 1, 'CONFIRMER'),
(2, 1, 'Période d''essai (mois)', 'NOMBRE', '3', 2, 'CONFIRMER'),
(3, 1, 'Télétravail possible', 'BOOLEEN', 'OUI', 3, 'CONFIRMER')
ON CONFLICT(id_condition) DO NOTHING;
INSERT INTO demande_recrutement (id_demande, id_poste, id_demandeur, effectif_demande, motif, salaire_par_personne, date_besoin, statut) VALUES
(1, 1, 4, 2, 'Renfort projet X', 800000, date('now', '+30 days'), 'EN_ATTENTE')
ON CONFLICT(id_demande) DO NOTHING;
INSERT INTO offre_emploi (id_offre, id_demande, titre, description, statut, cree_par) VALUES
(1, 1, 'Développeur Fullstack - Projet X', 'Nous cherchons un développeur fullstack.', 'EN_ATTENTE', 3)
ON CONFLICT(id_offre) DO NOTHING;
-- ========== OFFRE 2 : PUBLIEE (nouvelle demande 2) ==========
INSERT INTO demande_recrutement (id_demande, id_poste, id_demandeur, effectif_demande, motif, salaire_par_personne, date_besoin, statut)
SELECT 2, 1, 4, 1, 'Test candidatures', 800000, date('now', '+30 days'), 'VALIDE'
WHERE NOT EXISTS (SELECT 1 FROM demande_recrutement WHERE id_demande = 2);

INSERT INTO offre_emploi (id_offre, id_demande, titre, description, date_publication, statut, cree_par)
SELECT 2, 2, 'Développeur Backend — Projet Y', 'Offre publiée de test.', date('now'), 'PUBLIEE', 3
WHERE NOT EXISTS (SELECT 1 FROM offre_emploi WHERE id_offre = 2);

-- ========== OFFRE 3 : EN_ATTENTE (nouvelle demande 3) ==========
INSERT INTO demande_recrutement (id_demande, id_poste, id_demandeur, effectif_demande, motif, salaire_par_personne, date_besoin, statut)
SELECT 3, 1, 4, 1, 'Test validation', 800000, date('now', '+30 days'), 'EN_ATTENTE'
WHERE NOT EXISTS (SELECT 1 FROM demande_recrutement WHERE id_demande = 3);

INSERT INTO offre_emploi (id_offre, id_demande, titre, description, statut, cree_par)
SELECT 3, 3, 'Designer UI/UX — Projet Z', 'Offre en attente pour re-tester.', 'EN_ATTENTE', 3
WHERE NOT EXISTS (SELECT 1 FROM offre_emploi WHERE id_offre = 3);

-- ========== SECOND CANDIDAT (pour 2 candidatures distinctes) ==========
INSERT INTO utilisateur (id_utilisateur, email, mot_de_passe_hash, role, actif)
SELECT 5, 'candidat2@test.local',
       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
       'CANDIDAT', TRUE
WHERE NOT EXISTS (SELECT 1 FROM utilisateur WHERE email = 'candidat2@test.local');

INSERT INTO personne (id_personne, id_utilisateur, nom, prenom, telephone)
SELECT 2, 5, 'Rabe', 'Marie', '0330000000'
WHERE NOT EXISTS (SELECT 1 FROM personne WHERE id_personne = 2);

INSERT INTO candidat (id_candidat, id_personne, niveau_etude, experience)
SELECT 2, 2, 'Bac+3', '2 ans'
WHERE NOT EXISTS (SELECT 1 FROM candidat WHERE id_candidat = 2);

-- ========== CANDIDATURE 1 : RECUE (candidat 1 sur offre 2) ==========
INSERT INTO candidature (id_candidature, id_offre, id_candidat,
    cv_nom, cv_type_mime, cv_donnees,
    lettre_nom, lettre_type_mime, lettre_donnees,
    cin_nom, cin_type_mime, cin_donnees,
    motivation, statut)
SELECT 1, 2, 1,
    'cv.pdf','application/pdf', X'',
    'lettre.pdf','application/pdf', X'',
    'cin.jpg','image/jpeg', X'',
    'Je suis motivé.', 'RECUE'
WHERE NOT EXISTS (SELECT 1 FROM candidature WHERE id_candidature = 1);

-- ========== CANDIDATURE 2 : PRESELECTIONNEE (candidat 2 sur offre 2) ==========
INSERT INTO candidature (id_candidature, id_offre, id_candidat,
    cv_nom, cv_type_mime, cv_donnees,
    lettre_nom, lettre_type_mime, lettre_donnees,
    cin_nom, cin_type_mime, cin_donnees,
    motivation, statut)
SELECT 2, 2, 2,
    'cv2.pdf','application/pdf', X'',
    'lettre2.pdf','application/pdf', X'',
    'cin2.jpg','image/jpeg', X'',
    'Disponible immédiatement.', 'PRESELECTIONNEE'
WHERE NOT EXISTS (SELECT 1 FROM candidature WHERE id_candidature = 2);

-- ========== ENTRETIEN + ÉVALUATIONS pour candidature 2 ==========
INSERT INTO entretien (id_entretien, id_candidature, id_organisateur, date_entretien, lieu, statut)
SELECT 1, 2, 3, datetime('now', '-2 days'), 'Salle A', 'REALISE'
WHERE NOT EXISTS (SELECT 1 FROM entretien WHERE id_entretien = 1);

INSERT INTO evaluation_candidat (id_evaluation, id_candidature, id_evaluateur,
    id_entretien, type_evaluation, note, commentaire)
SELECT 1, 2, 3, 1, 'ENTRETIEN', 8.5, 'Très bon entretien.'
WHERE NOT EXISTS (SELECT 1 FROM evaluation_candidat WHERE id_evaluation = 1);

INSERT INTO evaluation_candidat (id_evaluation, id_candidature, id_evaluateur,
    id_entretien, type_evaluation, note, commentaire)
SELECT 2, 2, 3, NULL, 'DOSSIER', 7.0, 'Dossier solide.'
WHERE NOT EXISTS (SELECT 1 FROM evaluation_candidat WHERE id_evaluation = 2);

-- ========== Recaler les séquences ==========
COMMIT;


-- ================================================================================================================


BEGIN;

-- ==========================================================
-- 2 nouveaux candidats (user 7, 8)
-- ==========================================================
INSERT INTO utilisateur (id_utilisateur, email, mot_de_passe_hash, role, actif)
SELECT 7, 'candidat3@test.local',
       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
       'CANDIDAT', TRUE
WHERE NOT EXISTS (SELECT 1 FROM utilisateur WHERE email = 'candidat3@test.local');

INSERT INTO utilisateur (id_utilisateur, email, mot_de_passe_hash, role, actif)
SELECT 8, 'candidat4@test.local',
       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
       'CANDIDAT', TRUE
WHERE NOT EXISTS (SELECT 1 FROM utilisateur WHERE email = 'candidat4@test.local');

INSERT INTO personne (id_personne, id_utilisateur, nom, prenom, telephone)
SELECT 3, 7, 'Randria', 'Alice', '0321111111'
WHERE NOT EXISTS (SELECT 1 FROM personne WHERE id_personne = 3);

INSERT INTO personne (id_personne, id_utilisateur, nom, prenom, telephone)
SELECT 4, 8, 'Rasoa', 'Bobby', '0322222222'
WHERE NOT EXISTS (SELECT 1 FROM personne WHERE id_personne = 4);

INSERT INTO candidat (id_candidat, id_personne, niveau_etude, experience)
SELECT 3, 3, 'Bac+5', 'Développeur senior'
WHERE NOT EXISTS (SELECT 1 FROM candidat WHERE id_candidat = 3);

INSERT INTO candidat (id_candidat, id_personne, niveau_etude, experience)
SELECT 4, 4, 'Bac+2', 'Débutant motivé'
WHERE NOT EXISTS (SELECT 1 FROM candidat WHERE id_candidat = 4);

-- ==========================================================
-- 2 nouvelles offres PUBLIEE (offre 4 et 5) — demande 4 et 5
-- (id_demande doit être UNIQUE dans offre_emploi)
-- ==========================================================

-- Demande 4 + offre 4
INSERT INTO demande_recrutement (id_demande, id_poste, id_demandeur, effectif_demande, motif, salaire_par_personne, date_besoin, statut)
SELECT 4, 1, 4, 1, 'Test postuler — offre A', 900000, date('now', '+30 days'), 'VALIDE'
WHERE NOT EXISTS (SELECT 1 FROM demande_recrutement WHERE id_demande = 4);

INSERT INTO offre_emploi (id_offre, id_demande, titre, description, date_publication, date_limite, statut, cree_par)
SELECT 4, 4, 'Ingénieur DevOps — Projet A',
       'Offre de test pour vérifier le dépôt de candidature (A).',
       date('now'), date('now', '+60 days'), 'PUBLIEE', 3
WHERE NOT EXISTS (SELECT 1 FROM offre_emploi WHERE id_offre = 4);

-- Demande 5 + offre 5
INSERT INTO demande_recrutement (id_demande, id_poste, id_demandeur, effectif_demande, motif, salaire_par_personne, date_besoin, statut)
SELECT 5, 1, 4, 1, 'Test postuler — offre B', 850000, date('now', '+30 days'), 'VALIDE'
WHERE NOT EXISTS (SELECT 1 FROM demande_recrutement WHERE id_demande = 5);

INSERT INTO offre_emploi (id_offre, id_demande, titre, description, date_publication, date_limite, statut, cree_par)
SELECT 5, 5, 'Analyste QA — Projet B',
       'Offre de test pour vérifier le dépôt de candidature (B).',
       date('now'), date('now', '+60 days'), 'PUBLIEE', 3
WHERE NOT EXISTS (SELECT 1 FROM offre_emploi WHERE id_offre = 5);

-- ==========================================================
-- Recalage des séquences
-- ==========================================================
COMMIT;