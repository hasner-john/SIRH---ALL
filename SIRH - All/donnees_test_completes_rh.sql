-- Ce seed est rejouable : INSERT OR IGNORE preserve les enregistrements existants.
-- Executez-le apres base_recrutement.sql.
-- ============================================================================
-- DONNEES DE TEST RH : recrutement + blocs 6, 7, 8, 9, 10, 11 et 12
-- Compatible SQLite3. A executer APRES base_recrutement.sql.
-- Jeu de demonstration : identifiants fixes, documents BLOB fictifs.
-- Ne pas executer sur une base de production.
-- ============================================================================
PRAGMA foreign_keys = ON;
BEGIN;

-- 1. ORGANISATION, COMPTES ET PROFILS
INSERT OR IGNORE INTO departement(id_departement,nom,description) VALUES
(1,'Informatique','Developpement et support'),(2,'Ressources humaines','Gestion RH'),
(3,'Finance','Comptabilite et controle');

INSERT OR IGNORE INTO utilisateur(id_utilisateur,email,mot_de_passe_hash,role) VALUES
(1,'admin.demo@entreprise.test','HASH_DEMO_ADMIN','ADMINISTRATEUR'),
(2,'rh.demo@entreprise.test','HASH_DEMO_RH','RH'),
(3,'direction.demo@entreprise.test','HASH_DEMO_DIRECTION','DIRECTION'),
(4,'finance.demo@entreprise.test','HASH_DEMO_FINANCE','FINANCE'),
(5,'chef.info@entreprise.test','HASH_DEMO_DEPT','DEPARTEMENT'),
(6,'rh2.demo@entreprise.test','HASH_DEMO_RH2','RH'),
(11,'candidat1@demo.test','HASH_DEMO_C1','CANDIDAT'),
(12,'candidat2@demo.test','HASH_DEMO_C2','CANDIDAT'),
(13,'candidat3@demo.test','HASH_DEMO_C3','CANDIDAT'),
(14,'candidat4@demo.test','HASH_DEMO_C4','CANDIDAT'),
(15,'candidat5@demo.test','HASH_DEMO_C5','CANDIDAT'),
(16,'candidat6@demo.test','HASH_DEMO_C6','CANDIDAT'),
(17,'candidat7@demo.test','HASH_DEMO_C7','CANDIDAT'),
(18,'candidat8@demo.test','HASH_DEMO_C8','CANDIDAT');

INSERT OR IGNORE INTO utilisateur_departement VALUES (5,1),(2,2),(4,3);

INSERT OR IGNORE INTO personne(id_personne,id_utilisateur,nom,prenom,telephone,adresse,date_naissance) VALUES
(101,11,'RAKOTO','Miora','0340000001','Antananarivo','1998-04-12'),
(102,12,'RABE','Toky','0340000002','Antananarivo','1995-08-20'),
(103,13,'RANDRIA','Fanja','0340000003','Toamasina','1999-02-10'),
(104,14,'ANDRIAM','Hery','0340000004','Antsirabe','1994-11-01'),
(105,15,'RAZAFI','Lina','0340000005','Antananarivo','2000-06-05'),
(106,16,'RANAIVO','Solo','0340000006','Fianarantsoa','1992-03-17'),
(107,17,'RAVELO','Noro','0340000007','Antananarivo','1997-09-09'),
(108,18,'RANDRI','Tiana','0340000008','Mahajanga','1996-12-21');

INSERT OR IGNORE INTO candidat(id_candidat,id_personne,niveau_etude,experience) VALUES
(1,101,'Master Informatique','3 ans developpement'),
(2,102,'Licence Informatique','2 ans support'),
(3,103,'Master RH','4 ans administration'),
(4,104,'Licence Gestion','3 ans comptabilite'),
(5,105,'Licence Informatique','Debutante'),
(6,106,'Master Finance','6 ans finance'),
(7,107,'Licence RH','2 ans recrutement'),
(8,108,'BTS Informatique','1 an maintenance');

INSERT OR IGNORE INTO poste(id_poste,id_departement,libelle,description,cree_par) VALUES
(1,1,'Developpeur logiciel','Developpement et maintenance applicative',5),
(2,2,'Assistant RH','Administration du personnel',2),
(3,3,'Comptable','Comptabilite generale',4),
(4,1,'Technicien support','Support utilisateurs',5);

INSERT OR IGNORE INTO condition_contrat(id_condition,id_poste,libelle,type_valeur,valeur_condition,ordre_affichage,statut,modifie_par) VALUES
(1,1,'Salaire mensuel','MONTANT','1200000',1,'CONFIRMER',2),
(2,1,'Lieu de travail','TEXTE','Antananarivo',2,'CONFIRMER',2),
(3,1,'Teletravail','BOOLEEN','Oui',3,'CONFIRMER',2),
(4,2,'Salaire mensuel','MONTANT','650000',1,'CONFIRMER',2),
(5,3,'Salaire mensuel','MONTANT','900000',1,'CONFIRMER',2),
(6,4,'Salaire mensuel','MONTANT','450000',1,'CONFIRMER',2);

INSERT OR IGNORE INTO demande_recrutement(id_demande,id_poste,id_demandeur,effectif_demande,motif,salaire_par_personne,date_besoin,statut) VALUES
(1,1,5,2,'Renforcement equipe developpement',1200000,'2026-01-15','VALIDE'),
(2,2,2,1,'Remplacement assistant RH',650000,'2026-02-01','VALIDE'),
(3,3,4,1,'Renforcement comptabilite',900000,'2026-03-01','REFUSE'),
(4,4,5,1,'Besoin temporaire support',450000,'2026-04-01','ANNULEE');

INSERT OR IGNORE INTO validation_demande(id_validation,id_demande,id_validateur,type_validation,decision,commentaire) VALUES
(1,1,2,'RH','VALIDE','Dossier complet'),(2,1,3,'DIRECTION','VALIDE','Budget approuve'),
(3,2,2,'RH','VALIDE','Besoin confirme'),(4,2,3,'DIRECTION','VALIDE','Accord direction'),
(5,3,2,'RH','VALIDE','Avis RH favorable'),(6,3,3,'DIRECTION','REFUSE','Budget insuffisant'),
(7,4,2,'RH','REFUSE','Demande annulee avant publication');

INSERT OR IGNORE INTO historique_demande(id_historique,id_demande,ancien_statut,nouveau_statut,id_utilisateur,commentaire) VALUES
(1,1,'BROUILLON','EN_ATTENTE',5,'Soumission'),(2,1,'EN_ATTENTE','VALIDE',3,'Validation'),
(3,2,'BROUILLON','EN_ATTENTE',2,'Soumission'),(4,2,'EN_ATTENTE','VALIDE',3,'Validation'),
(5,3,'EN_ATTENTE','REFUSE',3,'Budget'),(6,4,'BROUILLON','ANNULEE',5,'Annulation');

INSERT OR IGNORE INTO offre_emploi(id_offre,id_demande,titre,description,date_publication,date_limite,statut,cree_par) VALUES
(1,1,'Developpeur logiciel','Developpement d applications internes','2026-01-20','2026-03-20','PUBLIEE',2),
(2,2,'Assistant RH','Gestion administrative RH','2026-02-05','2026-04-05','PUBLIEE',2),
(3,3,'Comptable - offre refusee','Offre de demonstration non publiee',NULL,NULL,'REFUSEE',2),
(4,4,'Technicien support - brouillon','Offre non publiee',NULL,NULL,'BROUILLON',5);

INSERT OR IGNORE INTO validation_offre(id_validation_offre,id_offre,id_validateur,decision,commentaire) VALUES
(1,1,3,'VALIDE','Publication autorisee'),(2,2,3,'VALIDE','Publication autorisee'),
(3,3,3,'REFUSEE','Demande de recrutement refusee'),(4,4,2,'A_CORRIGER','Completer la description');

INSERT OR IGNORE INTO historique_offre_emploi(id_historique,id_offre,ancien_statut,nouveau_statut,id_utilisateur,commentaire) VALUES
(1,1,'BROUILLON','PUBLIEE',2,'Offre publiee'),(2,2,'BROUILLON','PUBLIEE',2,'Offre publiee'),
(3,3,'EN_ATTENTE','REFUSEE',3,'Refus direction');

-- 2. CANDIDATURES, EVALUATIONS, NEGOCIATIONS ET CONTRATS
INSERT OR IGNORE INTO candidature(id_candidature,id_offre,id_candidat,cv_nom,cv_type_mime,cv_donnees,lettre_nom,lettre_type_mime,lettre_donnees,cin_nom,cin_type_mime,cin_donnees,motivation,statut) VALUES
(1,1,1,'cv_miora.pdf','application/pdf',X'255044462D44454D4F','lettre_miora.pdf','application/pdf',X'255044462D44454F','cin_miora.pdf','application/pdf',X'255044462D44454F','Je souhaite rejoindre votre equipe.','RETENUE'),
(2,1,2,'cv_toky.pdf','application/pdf',X'255044462D44454F','lettre_toky.pdf','application/pdf',X'255044462D44454F','cin_toky.pdf','application/pdf',X'255044462D44454F','Candidature developpeur.','REFUSEE'),
(3,2,3,'cv_fanja.pdf','application/pdf',X'255044462D44454F','lettre_fanja.pdf','application/pdf',X'255044462D44454F','cin_fanja.pdf','application/pdf',X'255044462D44454F','Candidature RH.','RETENUE'),
(4,2,7,'cv_noro.pdf','application/pdf',X'255044462D44454F','lettre_noro.pdf','application/pdf',X'255044462D44454F','cin_noro.pdf','application/pdf',X'255044462D44454F','Candidature RH.','ABANDONNEE'),
(5,1,5,'cv_lina.pdf','application/pdf',X'255044462D44454F','lettre_lina.pdf','application/pdf',X'255044462D44454F','cin_lina.pdf','application/pdf',X'255044462D44454F','Premiere experience.','PRESELECTIONNEE'),
(6,1,8,'cv_tiana.pdf','application/pdf',X'255044462D44454F','lettre_tiana.pdf','application/pdf',X'255044462D44454F','cin_tiana.pdf','application/pdf',X'255044462D44454F','Candidature support.','RECUE'),
(7,2,4,'cv_hery.pdf','application/pdf',X'255044462D44454F','lettre_hery.pdf','application/pdf',X'255044462D44454F','cin_hery.pdf','application/pdf',X'255044462D44454F','Candidature RH secondaire.','REFUSEE'),
(8,2,6,'cv_solo.pdf','application/pdf',X'255044462D44454F','lettre_solo.pdf','application/pdf',X'255044462D44454F','cin_solo.pdf','application/pdf',X'255044462D44454F','Profil polyvalent.','RECUE');

INSERT OR IGNORE INTO historique_candidature(id_historique,id_candidature,ancien_statut,nouveau_statut,id_utilisateur,commentaire) VALUES
(1,1,'RECUE','PRESELECTIONNEE',2,'Dossier conforme'),(2,1,'PRESELECTIONNEE','RETENUE',2,'Candidat retenu'),
(3,2,'RECUE','REFUSEE',2,'Experience insuffisante'),(4,3,'RECUE','PRESELECTIONNEE',2,'Profil interessant'),
(5,3,'PRESELECTIONNEE','RETENUE',2,'Candidat retenu'),(6,4,'RECUE','ABANDONNEE',2,'Retrait candidat'),
(7,5,'RECUE','PRESELECTIONNEE',2,'A evaluer'),(8,7,'RECUE','REFUSEE',2,'Profil non adapte');

INSERT OR IGNORE INTO entretien(id_entretien,id_candidature,id_organisateur,date_entretien,lieu,notes,statut) VALUES
(1,1,2,'2026-03-02 09:00:00','Siege - salle A','Bon niveau technique','REALISE'),
(2,2,2,'2026-03-03 10:00:00','Siege - salle A','Reponses insuffisantes','REALISE'),
(3,3,2,'2026-03-04 09:00:00','Siege - salle B','Bonne communication','REALISE'),
(4,5,2,'2026-03-05 14:00:00','Visioconference','Entretien a venir','PLANIFIE'),
(5,4,2,'2026-03-06 14:00:00','Siege','Candidat absent','ANNULE');

INSERT OR IGNORE INTO test_technique(id_test,id_candidature,id_organisateur,intitule,description,date_test,statut) VALUES
(1,1,5,'Test SQL et Java','Requetes et POO','2026-03-07 09:00:00','REALISE'),
(2,2,5,'Test SQL et Java','Requetes et POO','2026-03-07 14:00:00','REALISE'),
(3,3,2,'Cas pratique RH','Gestion dossier salarie','2026-03-08 09:00:00','REALISE'),
(4,5,5,'Test technique','Evaluation initiale','2026-03-15 09:00:00','PLANIFIE');

INSERT OR IGNORE INTO evaluation_candidat(id_evaluation,id_candidature,id_evaluateur,id_entretien,id_test,type_evaluation,note,commentaire) VALUES
(1,1,2,NULL,NULL,'DOSSIER',9,'Dossier complet'),
(2,1,2,1,NULL,'ENTRETIEN',8.5,'Bonne communication'),
(3,1,5,NULL,1,'TEST_TECHNIQUE',9,'Tres bon resultat'),
(4,2,2,NULL,NULL,'DOSSIER',6,'Dossier moyen'),
(5,2,2,2,NULL,'ENTRETIEN',4,'Insuffisant'),
(6,2,5,NULL,2,'TEST_TECHNIQUE',3,'Bases a renforcer'),
(7,3,2,NULL,NULL,'DOSSIER',8,'Profil adapte'),
(8,3,2,3,NULL,'ENTRETIEN',8,'Bonne posture'),
(9,3,2,NULL,3,'TEST_TECHNIQUE',7.5,'Bon cas pratique');

INSERT OR IGNORE INTO requete_condition_contrat(id_requete,id_candidature,id_condition,valeur_proposee,commentaire_candidat,statut) VALUES
(1,1,1,'1300000','Proposition salariale','ACCEPTEE_DIRECTION'),
(2,1,3,'Non','Souhaite travailler sur site','REFUSEE_RH'),
(3,3,4,'700000','Demande de revalorisation','REFUSEE_DIRECTION'),
(4,5,1,'1250000','Proposition en attente','SOUMISE');

INSERT OR IGNORE INTO historique_requete_condition(id_historique,id_requete,ancien_statut,nouveau_statut,id_utilisateur,commentaire) VALUES
(1,1,'SOUMISE','ACCEPTEE_RH',2,'Avis RH favorable'),
(2,1,'ACCEPTEE_RH','ACCEPTEE_DIRECTION',3,'Accord final'),
(3,2,'SOUMISE','REFUSEE_RH',2,'Condition maintenue'),
(4,3,'ACCEPTEE_RH','REFUSEE_DIRECTION',3,'Budget non disponible');

INSERT OR IGNORE INTO contrat(id_contrat,id_candidature,reference,type_contrat,date_debut,date_fin,salaire,periodicite_salaire,lieu_travail,missions,horaires,conges_absences,statut,date_signature) VALUES
(1,1,'CTR-2026-001','CDI','2026-04-01',NULL,1200000,'MENSUEL','Antananarivo','Developpement logiciel','40h/semaine','Conges selon reglement','SIGNE','2026-03-20 10:00:00'),
(2,3,'CTR-2026-002','CDD','2026-04-15','2027-04-14',650000,'MENSUEL','Antananarivo','Administration RH','40h/semaine','Conges selon reglement','SIGNE','2026-04-01 10:00:00'),
(3,5,'CTR-2026-003','CE','2026-05-01','2026-10-31',380000,'MENSUEL','Antananarivo','Appui developpement','40h/semaine','Selon convention','SIGNE','2026-04-20 10:00:00'),
(4,6,'CTR-2026-004','CDI','2026-06-01',NULL,450000,'MENSUEL','Antananarivo','Support informatique','40h/semaine','Conges selon reglement','SIGNE','2026-05-20 10:00:00'),
(5,7,'CTR-2026-005','CDD','2026-06-01','2026-12-31',900000,'MENSUEL','Antananarivo','Comptabilite','40h/semaine','Conges selon reglement','SIGNE','2026-05-25 10:00:00'),
(6,8,'CTR-2026-006','CDI','2026-07-01',NULL,550000,'MENSUEL','Antananarivo','Gestion finance','40h/semaine','Conges selon reglement','SIGNE','2026-06-20 10:00:00'),
(7,1,'CTR-2026-001','CDI','2026-04-01',NULL,1200000,'MENSUEL','Antananarivo','Developpement logiciel','40h/semaine','Conges selon reglement','SIGNE','2026-03-20 10:00:00');

INSERT OR IGNORE INTO contrat_condition(id_contrat_condition,id_contrat,libelle,type_valeur,valeur_finale,ordre_affichage) VALUES
(1,1,'Salaire mensuel','MONTANT','1200000',1),(2,1,'Lieu de travail','TEXTE','Antananarivo',2),(3,1,'Teletravail','BOOLEEN','Oui',3),
(4,2,'Salaire mensuel','MONTANT','650000',1),(5,3,'Salaire mensuel','MONTANT','380000',1),
(6,4,'Salaire mensuel','MONTANT','450000',1),(7,5,'Salaire mensuel','MONTANT','900000',1),
(8,6,'Salaire mensuel','MONTANT','550000',1);

INSERT OR IGNORE INTO employe(id_employe,id_contrat,id_personne,matricule,date_embauche,statut,numero_cnaps,categorie,secteur) VALUES
(1,1,101,'EMP-001','2026-04-01','ACTIF','CN-0001','5B','NON_AGRICOLE'),
(2,2,103,'EMP-002','2026-04-15','ACTIF','CN-0002','4A','NON_AGRICOLE'),
(3,3,105,'EMP-003','2026-05-01','PARTI','CN-0003','1A','NON_AGRICOLE'),
(4,4,108,'EMP-004','2026-06-01','ACTIF','CN-0004','3A','NON_AGRICOLE'),
(5,5,104,'EMP-005','2026-06-01','ACTIF','CN-0005','4A','NON_AGRICOLE'),
(6,6,106,'EMP-006','2026-07-01','ACTIF','CN-0006','5A','AGRICOLE');

INSERT OR IGNORE INTO affectation(id_affectation,id_employe,id_departement,date_debut,date_fin,fonction) VALUES
(1,1,1,'2026-04-01',NULL,'Developpeur junior'),
(2,2,2,'2026-04-15',NULL,'Assistant RH'),
(3,3,1,'2026-05-01','2026-10-31','Developpeur stagiaire'),
(4,4,1,'2026-06-01',NULL,'Technicien support'),
(5,5,3,'2026-06-01',NULL,'Comptable'),
(6,6,3,'2026-07-01',NULL,'Analyste financier');

INSERT OR IGNORE INTO historique_contrat(id_historique,id_contrat,ancien_statut,nouveau_statut,id_utilisateur,commentaire) VALUES
(1,1,'BROUILLON','A_SIGNER',2,'Contrat transmis'),(2,1,'A_SIGNER','SIGNE',1,'Contrat signe'),
(3,2,'BROUILLON','SIGNE',2,'Contrat signe'),(4,3,'BROUILLON','SIGNE',2,'Contrat signe'),
(5,4,'BROUILLON','SIGNE',2,'Contrat signe'),(6,5,'BROUILLON','SIGNE',2,'Contrat signe'),
(7,6,'BROUILLON','SIGNE',2,'Contrat signe');

INSERT OR IGNORE INTO historique_employe(id_historique,id_employe,ancien_statut,nouveau_statut,id_utilisateur,commentaire) VALUES
(1,1,NULL,'ACTIF',2,'Embauche'),(2,2,NULL,'ACTIF',2,'Embauche'),
(3,3,NULL,'ACTIF',2,'Embauche'),(4,3,'ACTIF','PARTI',2,'Fin de mission'),
(5,4,NULL,'ACTIF',2,'Embauche'),(6,5,NULL,'ACTIF',2,'Embauche'),(7,6,NULL,'ACTIF',2,'Embauche');

INSERT OR IGNORE INTO historique_salaire(id_historique,id_employe,ancien_salaire,nouveau_salaire,periodicite_salaire,motif,id_utilisateur,date_effet) VALUES
(1,1,NULL,1200000,'MENSUEL','Embauche',2,'2026-04-01'),
(2,2,NULL,650000,'MENSUEL','Embauche',2,'2026-04-15'),
(3,3,NULL,380000,'MENSUEL','Embauche',2,'2026-05-01'),
(4,4,NULL,450000,'MENSUEL','Embauche',2,'2026-06-01'),
(5,5,NULL,900000,'MENSUEL','Embauche',2,'2026-06-01'),
(6,6,NULL,550000,'MENSUEL','Embauche',2,'2026-07-01');

INSERT OR IGNORE INTO document_employe(id_document,id_employe,type_document,nom_fichier,type_mime,donnees,date_expiration,commentaire,id_utilisateur) VALUES
(1,1,'CONTRAT','contrat_emp001.pdf','application/pdf',X'255044462D44454D4F','2028-12-31','Contrat signe',2),
(2,1,'CIN','cin_emp001.pdf','application/pdf',X'255044462D44454D4F',NULL,'Piece identite',2),
(3,2,'DIPLOME','diplome_emp002.pdf','application/pdf',X'255044462D44454D4F',NULL,'Diplome',2);

-- 3. BLOC 6 : TEMPS DE TRAVAIL, POINTAGE ET HEURES SUPPLEMENTAIRES
INSERT OR IGNORE INTO regime_travail(id_regime,libelle,heures_hebdomadaires) VALUES
(1,'Non agricole - 40h',40),(2,'Agricole - 48h',48);

INSERT OR IGNORE INTO horaire(id_horaire,id_regime,libelle,heure_debut,heure_fin,pause_minutes) VALUES
(1,1,'Jour standard','08:00','17:00',60),(2,2,'Agricole','07:00','16:00',60),
(3,1,'Nuit','22:00','06:00',0);

INSERT OR IGNORE INTO planning(id_planning,id_employe,id_horaire,date_planning,statut) VALUES
(1,1,1,'2026-11-02','VALIDE'),(2,1,1,'2026-11-03','VALIDE'),
(3,2,1,'2026-11-02','VALIDE'),(4,4,1,'2026-11-02','VALIDE'),
(5,5,1,'2026-11-02','VALIDE'),(6,6,2,'2026-11-02','VALIDE');

INSERT OR IGNORE INTO pointage(id_pointage,id_employe,date_pointage,heure_entree,heure_sortie,source,statut,commentaire) VALUES
(1,1,'2026-11-02','2026-11-02 08:00:00','2026-11-02 17:00:00','BADGE','VALIDE','Jour normal'),
(2,1,'2026-11-03','2026-11-03 08:25:00','2026-11-03 17:00:00','BADGE','VALIDE','Retard de 25 minutes'),
(3,2,'2026-11-02','2026-11-02 08:00:00','2026-11-02 17:00:00','MANUEL','CLOTURE','Saisie manuelle'),
(4,4,'2026-11-02','2026-11-02 08:00:00',NULL,'BADGE','OUVERT','Sortie manquante'),
(5,5,'2026-11-02',NULL,NULL,'IMPORT','CLOTURE','Absence totale'),
(6,6,'2026-11-02','2026-11-02 07:00:00','2026-11-02 16:00:00','BADGE','VALIDE','Secteur agricole');

INSERT OR IGNORE INTO retard(id_retard,id_pointage,minutes,motif,justifie,statut) VALUES
(1,2,25,'Transport',1,'JUSTIFIE'),(2,3,10,'Retard non justifie',0,'A_TRAITER');

INSERT OR IGNORE INTO temps_travaille(id_temps,id_pointage,minutes_travaillees,minutes_theoriques,ecart_minutes) VALUES
(1,1,480,480,0),(2,2,455,480,-25),(3,3,470,480,-10),(4,6,480,480,0);

INSERT OR IGNORE INTO anomalie_pointage(id_anomalie,id_pointage,id_employe,date_anomalie,type_anomalie,description,statut) VALUES
(1,4,4,'2026-11-02','SORTIE_MANQUANTE','Heure de sortie absente','OUVERTE'),
(2,5,5,'2026-11-02','ABSENCE','Absence constatee','OUVERTE');
INSERT OR IGNORE INTO heure_supplementaire(id_heure_supplementaire,id_employe,date_travail,heures,motif,statut,id_validateur,nuit) VALUES
(1,1,'2026-11-08',4,'HS semaine tranche 30%', 'VALIDE',2,0),
(2,1,'2026-11-15',3,'HS semaine tranche 50%', 'VALIDE',2,0),
(3,1,'2026-11-22',2,'Travail dimanche et nuit', 'VALIDE',2,1),
(4,2,'2026-11-10',2,'Heures supplementaires refusees', 'REFUSEE',2,0),
(5,6,'2026-11-12',3,'Heures supplementaires agricoles', 'A_VALIDER',NULL,0);

-- 4. BLOC 7 : CONGES ET ABSENCES
INSERT OR IGNORE INTO type_conge(id_type_conge,code,libelle,description,jours_annuels,justificatif_obligatoire,actif) VALUES
(1,'ANNUEL','Conge annuel','Droits annuels',30,0,1),
(2,'MALADIE','Conge maladie','Absence maladie',0,1,1),
(3,'SANS_SOLDE','Conge sans solde','Absence sans solde',0,0,1);

INSERT OR IGNORE INTO droit_conge(id_droit_conge,id_type_conge,code_role,anciennete_mois,jours_annuels) VALUES
(1,1,'EMPLOYE',0,30),(2,2,'EMPLOYE',0,0),(3,3,'EMPLOYE',0,0);
INSERT OR IGNORE INTO solde_conge(id_solde_conge,id_employe,id_type_conge,annee,acquis,pris,ajuste) VALUES
(1,1,1,2026,20,5,0),(2,2,1,2026,18,4,0),(3,3,1,2026,15,10,0),
(4,4,1,2026,12,2,0),(5,5,1,2026,12,3,0),(6,6,1,2026,10,1,0);
INSERT OR IGNORE INTO demande_conge(id_demande_conge,id_employe,id_type_conge,date_debut,date_fin,nombre_jours,motif,statut,id_validateur,commentaire_validation,date_decision) VALUES
(1,1,1,'2026-08-10','2026-08-14',5,'Conges annuels','VALIDE',2,'Accorde','2026-08-01'),
(2,1,1,'2026-12-01','2026-12-03',3,'Conges de fin annee','EN_ATTENTE',NULL,NULL,NULL),
(3,2,2,'2026-09-01','2026-09-02',2,'Maladie','VALIDE',2,'Justificatif fourni','2026-09-01'),
(4,4,1,'2026-10-05','2026-10-08',4,'Conges','REFUSE',2,'Periode non disponible','2026-10-01'),
(5,5,3,'2026-11-10','2026-11-11',2,'Convenance personnelle','ANNULE',NULL,'Annule par le salarie',NULL);
INSERT OR IGNORE INTO justificatif_conge(id_justificatif,id_demande_conge,nom_fichier,type_mime,donnees) VALUES
(1,3,'certificat_medical.pdf','application/pdf',X'255044462D44454D4F');
INSERT OR IGNORE INTO absence(id_absence,id_employe,date_debut,date_fin,type_absence,motif,justifiee,id_justificatif,cree_par) VALUES
(1,1,'2026-11-05','2026-11-05','ABSENCE','Absence non justifiee',0,NULL,2),
(2,2,'2026-09-01','2026-09-02','MALADIE','Arret maladie',1,1,2),
(3,5,'2026-11-12','2026-11-12','ABSENCE','Absence autorisee',1,NULL,2);

-- 5. BLOC 8 : PARAMETRES, VARIABLES ET BULLETINS DE PAIE
INSERT OR IGNORE INTO parametre_paie(code,valeur,secteur,date_effet,date_fin) VALUES
('SME',360000,'NON_AGRICOLE','2027-01-01',NULL),
('CNAPS_SALARIAL',1.2,'NON_AGRICOLE','2027-01-01',NULL);
INSERT OR IGNORE INTO bareme_irsa(borne_inf,borne_sup,taux,date_effet) VALUES
(0,350000,0,'2027-01-01'),(350000,400000,0.05,'2027-01-01'),
(400000,500000,0.10,'2027-01-01'),(500000,600000,0.15,'2027-01-01'),
(600000,4000000,0.20,'2027-01-01'),(4000000,NULL,0.25,'2027-01-01');

INSERT OR IGNORE INTO rubrique_paie(id_rubrique,code,libelle,nature,imposable) VALUES
(10,'PRIME','Prime exceptionnelle','GAIN',1),
(11,'INDEMNITE_NON_IMPOSABLE','Indemnite non imposable','GAIN',0),
(12,'HS_30','Heures supplementaires 30%','GAIN',1),
(13,'HS_50','Heures supplementaires 50%','GAIN',1),
(14,'DIMANCHE_NUIT','Majoration dimanche nuit','GAIN',1);

INSERT OR IGNORE INTO periode_paie(id_periode,mois,annee,date_paiement,statut) VALUES
(1,11,2026,'2026-11-30','CALCUL'),
(2,10,2026,'2026-10-31','CLOTURE'),
(3,12,2026,NULL,'OUVERT');

INSERT OR IGNORE INTO variable_paie(id_variable,id_employe,id_periode,id_rubrique,nombre,montant,motif) VALUES
(1,1,1,10,1,100000,'Prime de performance'),
(2,1,1,11,1,50000,'Indemnite transport non imposable'),
(3,1,1,12,4,0,'HS 30%'),
(4,1,1,13,3,0,'HS 50%'),
(5,1,1,14,2,0,'Dimanche et nuit'),
(6,2,1,10,1,25000,'Prime'),
(7,2,1,3,1,80000,'Avance sur salaire'),
(8,4,1,10,1,50000,'Prime'),
(9,5,1,10,1,150000,'Prime'),
(10,6,1,10,1,30000,'Prime agricole');

-- Bulletins de demonstration : montants coherents avec les bases de test.
-- Les montants sont des fixtures de test, le calcul applicatif doit les recalculer.
INSERT OR IGNORE INTO bulletin_paie(id_bulletin,id_periode,id_employe,salaire_base,base_mois,retenue_absence,indemnites,majorations,rappels,salaire_brut,cnaps_salarial,cnaps_patronal,ostie_salarial,ostie_patronal,net_imposable,irsa,total_retenues,net_a_payer,statut,heures_absence,mode_reglement) VALUES
(1,1,1,1200000,1180000,20000,100000,120000,0,1400000,14000,182000,14000,70000,1372000,181900,209900,1190100,'BROUILLON',2,'VIREMENT'),
(2,1,2,650000,550000,100000,25000,0,0,575000,5750,74750,5750,28750,563500,22025,93525,481475,'BROUILLON',8,'ESPECES'),
(3,2,3,380000,380000,0,0,0,0,380000,3800,49400,3800,19000,372400,1120,8720,371280,'PAIE',0,'VIREMENT'),
(4,1,4,450000,450000,0,50000,0,0,500000,5000,65000,5000,25000,490000,11500,21500,478500,'BROUILLON',0,'CHEQUE'),
(5,1,5,900000,900000,0,150000,0,0,1050000,10500,136500,10500,52500,1029000,113300,134300,915700,'BROUILLON',0,'VIREMENT'),
(6,1,6,550000,550000,0,30000,0,0,580000,34800,46400,5800,29000,539400,18440,59040,520960,'BROUILLON',0,'VIREMENT');

INSERT OR IGNORE INTO ligne_bulletin(id_ligne,id_bulletin,code,designation,base_nombre,taux,gain,retenue,ordre_affichage) VALUES
(1,1,'1000','Salaire de base ajuste',1,1,1180000,0,1),
(2,1,'1010','Retenue absence',2,1,0,20000,2),
(3,1,'2000','Prime exceptionnelle',1,1,100000,0,3),
(4,1,'3010','HS majorees',4,1.3,120000,0,4),
(5,1,'4010','CNaPS salariale',1400000,0.01,0,14000,5),
(6,1,'4020','OSTIE salariale',1400000,0.01,0,14000,6),
(7,1,'5000','IRSA progressif',1372000,NULL,0,194400,7);

-- 6. BLOC 9 : COMPETENCES, OBJECTIFS, EVALUATIONS ET FORMATIONS
INSERT OR IGNORE INTO competence(id_competence,code,libelle,description) VALUES
(1,'JAVA','Developpement Java','Programmation orientee objet'),
(2,'SQL','Bases de donnees SQL','Conception et requetes'),
(3,'GESTION_RH','Gestion RH','Administration du personnel'),
(4,'COMPTA','Comptabilite','Comptabilite generale'),
(5,'COMMUNICATION','Communication','Communication professionnelle');
INSERT OR IGNORE INTO poste_competence(id_poste_competence,id_poste,id_competence,niveau_attendu) VALUES
(1,1,1,4),(2,1,2,4),(3,2,3,4),(4,2,5,3),(5,3,4,4),(6,4,2,2);
INSERT OR IGNORE INTO employe_competence(id_employe_competence,id_employe,id_competence,niveau_acquis,date_evaluation,id_evaluateur) VALUES
(1,1,1,3,'2026-09-01',2),(2,1,2,2,'2026-09-01',2),
(3,2,3,4,'2026-09-02',2),(4,2,5,2,'2026-09-02',2),
(5,4,2,3,'2026-09-03',5),(6,5,4,4,'2026-09-03',4),
(7,6,4,2,'2026-09-03',4),(8,6,5,3,'2026-09-03',4);
INSERT OR IGNORE INTO objectif(id_objectif,id_employe,titre,description,date_debut,date_fin,resultat,statut) VALUES
(1,1,'Livrer le module paie','Livraison du module paie','2026-07-01','2026-12-31','En cours de realisation','EN_COURS'),
(2,2,'Fiabiliser les dossiers','Reduire les dossiers incomplets','2026-07-01','2026-12-31','Objectif atteint','ATTEINT'),
(3,4,'Resoudre les tickets','Respecter les delais de support','2026-07-01','2026-12-31',NULL,'OUVERT'),
(4,5,'Cloturer les comptes','Cloture mensuelle','2026-07-01','2026-12-31','Retard constate','NON_ATTEINT');
INSERT OR IGNORE INTO evaluation_performance(id_evaluation_performance,id_employe,id_evaluateur,periode,note_globale,commentaire,statut,date_evaluation) VALUES
(1,1,2,'S2-2026',15,'Bonne progression, SQL a renforcer','VALIDE','2026-09-15'),
(2,2,2,'S2-2026',17,'Tres bonne performance','VALIDE','2026-09-15'),
(3,4,5,'S2-2026',12,'Doit ameliorer la documentation','BROUILLON','2026-09-16'),
(4,5,4,'S2-2026',9,'Objectifs partiellement atteints','ARCHIVE','2026-09-16');
INSERT OR IGNORE INTO formation(id_formation,titre,description,id_competence,date_debut,date_fin,prestataire,cout,statut) VALUES
(1,'Java avance','Approfondissement Java',1,'2026-10-01','2026-10-05','Centre IT',250000,'TERMINEE'),
(2,'SQL avance','Optimisation et transactions',2,'2026-11-25','2026-11-28','Centre IT',180000,'PLANIFIEE'),
(3,'Communication RH','Communication interne',5,'2026-10-10','2026-10-11','Cabinet RH',100000,'ANNULEE');
INSERT OR IGNORE INTO formation_participant(id_participant,id_formation,id_employe,statut,niveau_avant,niveau_apres) VALUES
(1,1,1,'TERMINE',2,4),(2,2,1,'INSCRIT',2,NULL),
(3,1,4,'ABANDON',2,NULL),(4,3,2,'INSCRIT',2,NULL);
-- Reevaluation post-formation : niveau acquis augmente apres la formation.
INSERT OR IGNORE INTO employe_competence(id_employe_competence,id_employe,id_competence,niveau_acquis,date_evaluation,id_evaluateur) VALUES
(9,1,1,4,'2026-10-10',2);

-- 7. BLOC 10 : MOBILITE ET HISTORIQUE DE CARRIERE
INSERT OR IGNORE INTO mouvement_carriere(id_mouvement,id_employe,type_mouvement,ancien_poste,nouveau_poste,ancien_salaire,nouveau_salaire,id_departement,date_effet,motif,id_auteur) VALUES
(1,1,'PROMOTION','Developpeur junior','Developpeur confirme',1200000,1450000,1,'2026-10-01','Performance',2),
(2,2,'MUTATION','Assistant RH','Charge de recrutement',650000,700000,2,'2026-11-01','Reorganisation',2),
(3,4,'CHANGEMENT_STATUT','CDD','CDI',450000,450000,1,'2026-09-01','Titularisation',2),
(4,5,'REVISION_SALAIRE','Comptable','Comptable',900000,1000000,3,'2026-10-01','Revalorisation annuelle',4),
(5,6,'AFFECTATION','Analyste financier','Controleur de gestion',550000,580000,3,'2026-11-01','Mobilite interne',4);
INSERT OR IGNORE INTO affectation(id_affectation,id_employe,id_departement,date_debut,date_fin,fonction) VALUES
(7,1,1,'2026-10-01',NULL,'Developpeur confirme'),
(8,2,2,'2026-11-01',NULL,'Charge de recrutement'),
(9,5,3,'2026-10-01',NULL,'Comptable confirme'),
(10,6,3,'2026-11-01',NULL,'Controleur de gestion');
-- Les anciennes affectations sont fermees pour eviter tout chevauchement temporel.
UPDATE affectation SET date_fin='2026-09-30' WHERE id_affectation=1;
UPDATE affectation SET date_fin='2026-10-31' WHERE id_affectation=2;
UPDATE affectation SET date_fin='2026-09-30' WHERE id_affectation=6;

-- 8. BLOC 11 : DISCIPLINE, PROCEDURE CONTRADICTOIRE ET SANCTIONS
INSERT OR IGNORE INTO incident(id_incident,id_employe,date_incident,objet,description,statut,id_signaleur) VALUES
(1,1,'2026-08-10','Retard repetitif','Retards repetes constates','SANCTION',5),
(2,2,'2026-09-05','Erreur de dossier','Erreur administrative signalee','CLOTURE',2),
(3,4,'2026-10-12','Absence injustifiee','Absence sans justificatif','EXPLICATIONS',5),
(4,5,'2026-10-20','Non respect procedure','Incident en cours de traitement','REPONSE',4),
(5,6,'2026-11-01','Incident mineur','Signalement classe sans sanction','CLOTURE',4);
INSERT OR IGNORE INTO demande_explication(id_demande_explication,id_incident,date_demande,date_limite,texte,date_notification) VALUES
(1,1,'2026-08-11','2026-08-16','Veuillez fournir vos explications.','2026-08-11 09:00:00'),
(2,2,'2026-09-06','2026-09-11','Veuillez expliquer cette erreur.','2026-09-06 09:00:00'),
(3,3,'2026-10-13','2026-10-18','Veuillez justifier votre absence.','2026-10-13 09:00:00'),
(4,4,'2026-10-21','2026-10-26','Veuillez expliquer les faits.','2026-10-21 09:00:00'),
(5,5,'2026-11-02','2026-11-07','Merci de presenter vos observations.','2026-11-02 09:00:00');
INSERT OR IGNORE INTO reponse_explication(id_reponse,id_incident,date_reponse,texte,piece_jointe_nom,piece_jointe_mime,piece_jointe_donnees) VALUES
(1,1,'2026-08-13','Je reconnais les retards et presente mes excuses.',NULL,NULL,NULL),
(2,2,'2026-09-08','Une erreur de saisie a ete commise.','explication.pdf','application/pdf',X'255044462D44454D4F'),
(3,4,'2026-10-24','La procedure n etait pas suffisamment claire.',NULL,NULL,NULL),
(4,5,'2026-11-03','Il s agissait d un malentendu.',NULL,NULL,NULL);
INSERT OR IGNORE INTO sanction(id_sanction,id_incident,type_sanction,motif,date_decision,date_effet,id_decideur,statut,piece_notification) VALUES
(1,1,'AVERTISSEMENT','Retards repetes apres examen de la reponse','2026-08-15','2026-08-16',3,'EXECUTOIRE',X'255044462D44454D4F'),
(2,2,'BLAME','Manquement confirme apres procedure contradictoire','2026-09-10','2026-09-12',3,'NOTIFIEE',X'255044462D44454D4F');
-- Incident 3 sans reponse ni sanction : dossier encore en attente d'explications.
-- Incident 4 avec reponse mais sans decision : commission a traiter.
-- Incident 5 classe sans sanction : aucune ligne dans sanction.

-- 9. BLOC 12 : RUPTURE, STC, OFFBOARDING ET ATTESTATIONS
INSERT OR IGNORE INTO rupture_contrat(id_rupture,id_employe,motif,date_notification,date_sortie,duree_preavis_jours,motif_detail,statut,id_auteur) VALUES
(1,3,'FIN_CDD','2026-10-01','2026-10-31',30,'Fin de contrat a duree determinee','CLOTURE',2),
(2,2,'DEMISSION','2026-11-01','2026-11-30',30,'Demission volontaire','EN_COURS',2),
(3,4,'LICENCIEMENT_ECONOMIQUE','2026-10-15','2026-12-14',60,'Reorganisation economique','OUVERT',3),
(4,5,'LICENCIEMENT_FAUTE','2026-10-20','2026-11-19',30,'Faute grave - dossier de demonstration','VALIDEE',3),
(5,6,'RETRAITE','2026-09-01','2026-12-31',120,'Depart a la retraite','EN_COURS',2),
(6,1,'RUPTURE_CONVENTIONNELLE','2026-11-05','2026-12-05',30,'Accord entre les parties','OUVERT',2);

INSERT OR IGNORE INTO solde_tout_compte(id_stc,id_rupture,dernier_salaire,salaire_proratise,indemnite_conges,indemnite_rupture,avances,retenues,montant_net,statut) VALUES
(1,1,380000,380000,65000,0,0,0,445000,'PAIE'),
(2,2,650000,650000,90000,0,80000,10000,650000,'BROUILLON'),
(3,3,450000,225000,40000,150000,0,5000,410000,'BROUILLON'),
(4,4,1000000,633333,120000,300000,0,20000,1033333,'VALIDE'),
(5,5,580000,580000,150000,500000,0,0,1230000,'BROUILLON'),
(6,6,1450000,1450000,110000,200000,100000,0,1660000,'BROUILLON');

INSERT OR IGNORE INTO checklist_offboarding(id_checklist,id_rupture,element,valeur_statut,date_echeance,date_realisation,commentaire) VALUES
(1,1,'Dernier bulletin de paie','TERMINE','2026-10-31','2026-10-31 10:00:00','Bulletin etabli'),
(2,1,'Validation STC','TERMINE','2026-10-31','2026-10-31 11:00:00','STC paye'),
(3,1,'Restitution badge','TERMINE','2026-10-31','2026-10-30 15:00:00','Badge rendu'),
(4,1,'Restitution ordinateur','TERMINE','2026-10-31','2026-10-30 15:30:00','Materiel rendu'),
(5,1,'Coupure des acces','TERMINE','2026-10-31','2026-10-31 17:00:00','Acces desactives'),
(6,1,'Archivage dossier','TERMINE','2026-11-05','2026-11-02 09:00:00','Dossier archive'),
(7,2,'Dernier bulletin de paie','EN_COURS','2026-11-30',NULL,'En attente du dernier mois'),
(8,2,'Restitution badge','A_FAIRE','2026-11-30',NULL,NULL),
(9,2,'Restitution ordinateur','A_FAIRE','2026-11-30',NULL,NULL),
(10,2,'Coupure des acces','A_FAIRE','2026-11-30',NULL,'Programmee a la sortie'),
(11,3,'Validation STC','A_FAIRE','2026-12-14',NULL,NULL),
(12,3,'Restitution materiel','EN_COURS','2026-12-14',NULL,'Inventaire en cours'),
(13,4,'Restitution badge','TERMINE','2026-11-19','2026-11-18 10:00:00','Badge rendu'),
(14,4,'Coupure des acces','TERMINE','2026-11-19','2026-11-19 17:00:00','Acces coupes'),
(15,5,'Certificat de travail','A_FAIRE','2026-12-31',NULL,NULL),
(16,6,'Restitution materiel','NON_APPLICABLE','2026-12-05',NULL,'Aucun materiel attribue');

INSERT OR IGNORE INTO attestation_travail(id_attestation,id_rupture,type_attestation,reference,pdf_nom,pdf_type_mime,pdf_donnees) VALUES
(1,1,'CERTIFICAT_TRAVAIL','ATT-TRAV-2026-001','certificat_emp003.pdf','application/pdf',X'255044462D44454D4F'),
(2,1,'ATTESTATION_CNAPS','ATT-CNAPS-2026-001','cnaps_emp003.pdf','application/pdf',X'255044462D44454D4F'),
(3,1,'ATTESTATION_OSTIE','ATT-OSTIE-2026-001','ostie_emp003.pdf','application/pdf',X'255044462D44454D4F');

-- 10. AUDIT, ACCES, NOTIFICATIONS ET HISTORIQUES TRANSVERSAUX
INSERT OR IGNORE INTO journal_acces(id_journal,id_utilisateur,id_employe,action,ressource,adresse_ip,details) VALUES
(1,2,1,'CONSULTATION','BULLETIN_PAIE','127.0.0.1','Consultation bulletin de novembre'),
(2,4,5,'CONSULTATION','BULLETIN_PAIE','127.0.0.1','Consultation finance'),
(3,2,1,'MODIFICATION','DOSSIER_EMPLOYE','127.0.0.1','Mise a jour dossier'),
(4,3,4,'CONSULTATION','DOSSIER_DISCIPLINAIRE','127.0.0.1','Acces autorise direction'),
(5,2,3,'TELECHARGEMENT','CERTIFICAT_TRAVAIL','127.0.0.1','Telechargement certificat');

INSERT OR IGNORE INTO audit_rh(id_audit,id_utilisateur,action,objet,objet_id,ancienne_valeur,nouvelle_valeur,motif,adresse_ip) VALUES
(1,2,'CREATE','CONTRAT',1,NULL,'SIGNE','Embauche','127.0.0.1'),
(2,2,'UPDATE','EMPLOYE',3,'ACTIF','PARTI','Fin CDD','127.0.0.1'),
(3,3,'UPDATE','RUPTURE',4,'OUVERT','VALIDEE','Validation depart','127.0.0.1'),
(4,2,'CREATE','BULLETIN_PAIE',1,NULL,'BROUILLON','Calcul paie','127.0.0.1');

INSERT OR IGNORE INTO historique_formulaire_reponse(id_historique,id_utilisateur,type_formulaire,id_enregistrement,donnees,action) VALUES
(1,5,'DEMANDE_RECRUTEMENT',1,'{"effectif":2,"salaire":1200000}','CREATION'),
(2,2,'CANDIDATURE',1,'{"statut":"RETENUE"}','SOUMISSION'),
(3,2,'RUPTURE_CONTRAT',1,'{"motif":"FIN_CDD"}','CREATION');

INSERT OR IGNORE INTO notification(id_notification,id_utilisateur,titre,message,lien,lue,date_lecture) VALUES
(1,2,'Nouvelle candidature','Une candidature a ete recue.','/candidatures/6',0,NULL),
(2,3,'Validation requise','Une demande attend votre validation.','/demandes/1',1,'2026-01-21 08:00:00'),
(3,1,'Offboarding a terminer','Une checklist reste a completer.','/ruptures/2',0,NULL),
(4,4,'Bulletin disponible','Le bulletin de novembre est disponible.','/paie/1',0,NULL);


-- Donnees complementaires : cas limites et etats supplementaires.
INSERT OR IGNORE INTO utilisateur(id_utilisateur,email,mot_de_passe_hash,role) VALUES
(19,'candidat9@demo.test','HASH_DEMO_C9','CANDIDAT'),
(20,'candidat10@demo.test','HASH_DEMO_C10','CANDIDAT');
INSERT OR IGNORE INTO personne(id_personne,id_utilisateur,nom,prenom,telephone,adresse,date_naissance) VALUES
(109,19,'RAKOTOMALALA','Ando','0340000009','Antananarivo','1990-01-10'),
(110,20,'RABENIRINA','Mamy','0340000010','Antananarivo','1993-05-12');
INSERT OR IGNORE INTO candidat(id_candidat,id_personne,niveau_etude,experience) VALUES
(9,109,'Master Finance','10 ans direction financiere'),
(10,110,'Licence Informatique','2 ans developpement');
INSERT OR IGNORE INTO candidature(id_candidature,id_offre,id_candidat,cv_nom,cv_type_mime,cv_donnees,lettre_nom,lettre_type_mime,lettre_donnees,cin_nom,cin_type_mime,cin_donnees,motivation,statut) VALUES
(9,1,9,'cv_ando.pdf','application/pdf',X'255044462D44454D4F','lettre_ando.pdf','application/pdf',X'255044462D44454D4F','cin_ando.pdf','application/pdf',X'255044462D44454D4F','Profil senior pour test paie plafond','SELECTIONNEE'),
(10,1,10,'cv_mamy.pdf','application/pdf',X'255044462D44454D4F','lettre_mamy.pdf','application/pdf',X'255044462D44454D4F','cin_mamy.pdf','application/pdf',X'255044462D44454D4F','Candidature de demonstration','PRESELECTIONNEE');
INSERT OR IGNORE INTO contrat(id_contrat,id_candidature,reference,type_contrat,date_debut,date_fin,salaire,periodicite_salaire,lieu_travail,missions,horaires,conges_absences,statut,date_signature) VALUES
(7,9,'CTR-2026-007','CDI','2026-08-01',NULL,5000000,'MENSUEL','Antananarivo','Direction financiere','40h/semaine','Conges selon reglement','SIGNE','2026-07-20 10:00:00'),
(8,10,'CTR-2026-008','CDD','2026-12-01','2027-05-31',700000,'MENSUEL','Antananarivo','Developpement','40h/semaine','Conges selon reglement','A_SIGNER',NULL);
INSERT OR IGNORE INTO employe(id_employe,id_contrat,id_personne,matricule,date_embauche,statut,numero_cnaps,categorie,secteur) VALUES
(7,7,109,'EMP-007','2026-08-01','ACTIF','CN-0007','HC','NON_AGRICOLE');
INSERT OR IGNORE INTO affectation(id_affectation,id_employe,id_departement,date_debut,fonction) VALUES
(11,7,3,'2026-08-01','Directeur financier');
INSERT OR IGNORE INTO historique_contrat(id_historique,id_contrat,ancien_statut,nouveau_statut,id_utilisateur,commentaire) VALUES
(8,7,'BROUILLON','SIGNE',2,'Contrat senior signe'),
(9,8,'BROUILLON','A_SIGNER',2,'Contrat en attente de signature');
INSERT OR IGNORE INTO historique_employe(id_historique,id_employe,ancien_statut,nouveau_statut,id_utilisateur,commentaire) VALUES
(8,7,NULL,'ACTIF',2,'Embauche cadre');
INSERT OR IGNORE INTO historique_salaire(id_historique,id_employe,ancien_salaire,nouveau_salaire,periodicite_salaire,motif,id_utilisateur,date_effet) VALUES
(7,7,NULL,5000000,'MENSUEL','Embauche cadre',2,'2026-08-01');
INSERT OR IGNORE INTO bulletin_paie(id_bulletin,id_periode,id_employe,salaire_base,base_mois,retenue_absence,indemnites,majorations,rappels,salaire_brut,cnaps_salarial,cnaps_patronal,ostie_salarial,ostie_patronal,net_imposable,irsa,total_retenues,net_a_payer,statut,heures_absence,mode_reglement) VALUES
(7,1,7,5000000,5000000,0,0,0,0,5000000,28000,364000,50000,250000,4922000,938000,1016000,3984000,'BROUILLON',0,'VIREMENT');

INSERT OR IGNORE INTO demande_recrutement(id_demande,id_poste,id_demandeur,effectif_demande,motif,salaire_par_personne,date_besoin,statut) VALUES
(5,1,5,1,'Demande en cours de redaction',700000,'2027-01-01','BROUILLON'),
(6,4,5,1,'Demande soumise en attente de validation',500000,'2027-02-01','EN_ATTENTE');
INSERT OR IGNORE INTO demande_recrutement(id_demande,id_poste,id_demandeur,effectif_demande,motif,salaire_par_personne,date_besoin,statut) VALUES
(7,1,5,1,'Besoin historique cloture',1200000,'2026-01-15','VALIDE');
INSERT OR IGNORE INTO offre_emploi(id_offre,id_demande,titre,description,date_publication,date_limite,statut,cree_par) VALUES
(5,6,'Technicien support - en attente','Offre en cours de validation','2026-11-20','2026-12-20','EN_ATTENTE',2),
(6,5,'Developpeur - offre valide','Offre valide avant publication','2026-11-20','2026-12-20','VALIDE',2),
(7,7,'Ancienne offre fermee','Offre archivee','2026-01-20','2026-03-20','FERMEE',2);

COMMIT;

-- ============================================================================
-- CONTROLES RAPIDES APRES INSERTION
-- ============================================================================
-- SELECT statut, COUNT(*) FROM demande_recrutement GROUP BY statut;
-- SELECT statut, COUNT(*) FROM candidature GROUP BY statut;
-- SELECT secteur, COUNT(*) FROM employe GROUP BY secteur;
-- SELECT statut, COUNT(*) FROM bulletin_paie GROUP BY statut;
-- SELECT motif, COUNT(*) FROM rupture_contrat GROUP BY motif;
-- SELECT * FROM employe;


-- ============================================================================
-- REQUETES DE VERIFICATION (a lancer apres le chargement)
-- ============================================================================
-- 1. Repartition des statuts du recrutement
-- SELECT statut, COUNT(*) FROM demande_recrutement GROUP BY statut ORDER BY statut;
-- SELECT statut, COUNT(*) FROM offre_emploi GROUP BY statut ORDER BY statut;
-- SELECT statut, COUNT(*) FROM candidature GROUP BY statut ORDER BY statut;
-- SELECT type_contrat, statut, COUNT(*) FROM contrat GROUP BY type_contrat, statut;

-- 2. Paie : verifier les bulletins et les secteurs
-- SELECT e.matricule, e.secteur, b.salaire_brut, b.cnaps_salarial,
--        b.ostie_salarial, b.net_imposable, b.irsa, b.net_a_payer
-- FROM bulletin_paie b JOIN employe e ON e.id_employe=b.id_employe
-- ORDER BY b.id_bulletin;
-- SELECT e.matricule, b.salaire_brut, b.cnaps_salarial
-- FROM bulletin_paie b JOIN employe e ON e.id_employe=b.id_employe
-- WHERE b.salaire_brut > 2800000; -- CNaPS plafonnee

-- 3. Ecarts de competences (ecart positif = besoin de formation)
-- SELECT p.libelle AS poste, c.libelle AS competence,
--        pc.niveau_attendu, ec.niveau_acquis,
--        pc.niveau_attendu-ec.niveau_acquis AS ecart
-- FROM poste_competence pc
-- JOIN poste p ON p.id_poste=pc.id_poste
-- JOIN competence c ON c.id_competence=pc.id_competence
-- LEFT JOIN employe_competence ec ON ec.id_competence=pc.id_competence
-- ORDER BY p.libelle,c.libelle;

-- 4. Carriere : controle des affectations temporelles
-- SELECT id_employe, date_debut, date_fin, fonction
-- FROM affectation ORDER BY id_employe,date_debut;

-- 5. Discipline : aucun incident sans procedure contradictoire ne doit avoir de sanction
-- SELECT i.id_incident, i.statut, d.id_demande_explication,
--        r.id_reponse, s.id_sanction
-- FROM incident i
-- LEFT JOIN demande_explication d ON d.id_incident=i.id_incident
-- LEFT JOIN reponse_explication r ON r.id_incident=i.id_incident
-- LEFT JOIN sanction s ON s.id_incident=i.id_incident;

-- 6. Offboarding : progression des checklists
-- SELECT r.id_rupture, e.matricule, r.motif, r.statut,
--        c.element, c.valeur_statut
-- FROM rupture_contrat r
-- JOIN employe e ON e.id_employe=r.id_employe
-- JOIN checklist_offboarding c ON c.id_rupture=r.id_rupture
-- ORDER BY r.id_rupture,c.id_checklist;

-- 7. Integrite referentielle
-- PRAGMA foreign_key_check;

-- EXEMPLES DE TESTS NEGATIFS (decommenter et executer individuellement) :
-- Ces insertions doivent echouer a cause des contraintes CHECK / UNIQUE.
-- INSERT INTO employe(id_employe,id_contrat,id_personne,matricule,date_embauche,secteur)
-- VALUES(99,1,101,'EMP-001','2026-01-01','NON_AGRICOLE'); -- contrat/personne/matricule deja lies
-- INSERT INTO competence(id_competence,code,libelle)
-- VALUES(99,'JAVA','Doublon'); -- code UNIQUE
-- INSERT INTO evaluation_candidat(id_evaluation,id_candidature,id_evaluateur,type_evaluation,note)
-- VALUES(99,1,2,'DOSSIER',15); -- note > 10
-- INSERT INTO rupture_contrat(id_rupture,id_employe,motif,date_notification,date_sortie)
-- VALUES(99,1,'DEMISSION','2026-12-10','2026-12-01'); -- date_sortie anterieure
