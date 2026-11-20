<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Creates a complete, deterministic dataset for local demonstrations and tests.
 */
class DemoDatabaseSeeder
{
    public function initialize(bool $reset = false): void
    {
        $database = ROOTPATH . 'writable/database.db';

        if ($reset && is_file($database) && ! unlink($database)) {
            throw new RuntimeException('Impossible de supprimer la base SQLite existante.');
        }

        if (! is_file($database)) {
            $this->createDirectory(dirname($database));
            touch($database);
        }

        $pdo = new \PDO('sqlite:' . $database);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA foreign_keys = ON');

        $hasSchema = (bool) $pdo->query(
            "SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'utilisateur'"
        )->fetchColumn();

        if ($hasSchema && ! $reset && (int) $pdo->query('SELECT COUNT(*) FROM utilisateur')->fetchColumn() > 0) {
            throw new RuntimeException('La base contient déjà des données. Utilisez php spark db:reset --force.');
        }

        if (! $hasSchema) {
            $schema = ROOTPATH . 'base_recrutement.sql';
            if (! is_file($schema)) {
                throw new RuntimeException('Le fichier base_recrutement.sql est introuvable.');
            }
            $pdo->exec((string) file_get_contents($schema));
        }

        $pdo->beginTransaction();
        try {
            if ($reset) {
                $this->clearTables($pdo);
            }
            $this->seed($pdo);
            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    private function seed(\PDO $pdo): void
    {
        $year = (int) date('Y');
        $password = password_hash('password', PASSWORD_DEFAULT);

        $this->insert($pdo, 'utilisateur', [
            ['id_utilisateur' => 1, 'email' => 'admin@test.local', 'mot_de_passe_hash' => $password, 'role' => 'ADMINISTRATEUR', 'actif' => 1, 'date_creation' => '2025-01-01 08:00:00'],
            ['id_utilisateur' => 2, 'email' => 'rh@test.local', 'mot_de_passe_hash' => $password, 'role' => 'RH', 'actif' => 1, 'date_creation' => '2025-01-01 08:00:00'],
            ['id_utilisateur' => 3, 'email' => 'direction@test.local', 'mot_de_passe_hash' => $password, 'role' => 'DIRECTION', 'actif' => 1, 'date_creation' => '2025-01-01 08:00:00'],
            ['id_utilisateur' => 4, 'email' => 'departement@test.local', 'mot_de_passe_hash' => $password, 'role' => 'DEPARTEMENT', 'actif' => 1, 'date_creation' => '2025-01-01 08:00:00'],
            ['id_utilisateur' => 5, 'email' => 'finance@test.local', 'mot_de_passe_hash' => $password, 'role' => 'FINANCE', 'actif' => 1, 'date_creation' => '2025-01-01 08:00:00'],
            ['id_utilisateur' => 6, 'email' => 'candidat@test.local', 'mot_de_passe_hash' => $password, 'role' => 'CANDIDAT', 'actif' => 1, 'date_creation' => '2025-01-01 08:00:00'],
            ['id_utilisateur' => 7, 'email' => 'employe1@test.local', 'mot_de_passe_hash' => $password, 'role' => 'CANDIDAT', 'actif' => 1, 'date_creation' => '2025-01-01 08:00:00'],
            ['id_utilisateur' => 8, 'email' => 'employe2@test.local', 'mot_de_passe_hash' => $password, 'role' => 'CANDIDAT', 'actif' => 1, 'date_creation' => '2025-01-01 08:00:00'],
            ['id_utilisateur' => 9, 'email' => 'employe3@test.local', 'mot_de_passe_hash' => $password, 'role' => 'CANDIDAT', 'actif' => 1, 'date_creation' => '2025-01-01 08:00:00'],
        ]);
        $this->insert($pdo, 'departement', [
            ['id_departement' => 1, 'nom' => 'Ressources humaines', 'description' => 'Administration du personnel', 'actif' => 1],
            ['id_departement' => 2, 'nom' => 'Informatique', 'description' => 'Systèmes et développement', 'actif' => 1],
        ]);
        $this->insert($pdo, 'personne', [
            ['id_personne' => 1, 'id_utilisateur' => 2, 'nom' => 'Rakoto', 'prenom' => 'Hery', 'telephone' => '0340000001', 'adresse' => 'Antananarivo', 'date_naissance' => '1990-04-12'],
            ['id_personne' => 2, 'id_utilisateur' => 3, 'nom' => 'Rasoanaivo', 'prenom' => 'Miora', 'telephone' => '0340000002', 'adresse' => 'Antananarivo', 'date_naissance' => '1987-09-20'],
            ['id_personne' => 3, 'id_utilisateur' => 6, 'nom' => 'Andria', 'prenom' => 'Tovo', 'telephone' => '0340000003', 'adresse' => 'Fianarantsoa', 'date_naissance' => '1998-02-15'],
            ['id_personne' => 4, 'id_utilisateur' => 7, 'nom' => 'Rabe', 'prenom' => 'Lova', 'telephone' => '0340000004', 'adresse' => 'Antsirabe', 'date_naissance' => '1995-06-08'],
            ['id_personne' => 5, 'id_utilisateur' => 8, 'nom' => 'Rakotoarisoa', 'prenom' => 'Soa', 'telephone' => '0340000005', 'adresse' => 'Toamasina', 'date_naissance' => '1992-11-21'],
            ['id_personne' => 6, 'id_utilisateur' => 9, 'nom' => 'Randria', 'prenom' => 'Faly', 'telephone' => '0340000006', 'adresse' => 'Mahajanga', 'date_naissance' => '1997-01-17'],
        ]);
        $this->insert($pdo, 'poste', [
            ['id_poste' => 1, 'id_departement' => 1, 'libelle' => 'Responsable RH', 'description' => 'Gestion administrative et sociale', 'statut' => 'ACTIF', 'cree_par' => 3, 'date_creation' => '2025-01-02 09:00:00'],
            ['id_poste' => 2, 'id_departement' => 2, 'libelle' => 'Développeur logiciel', 'description' => 'Développement des applications', 'statut' => 'ACTIF', 'cree_par' => 3, 'date_creation' => '2025-01-02 09:00:00'],
        ]);
        $this->insert($pdo, 'candidat', [
            ['id_candidat' => 1, 'id_personne' => 3, 'niveau_etude' => 'Master informatique', 'experience' => '2 ans'],
            ['id_candidat' => 2, 'id_personne' => 4, 'niveau_etude' => 'Licence informatique', 'experience' => '3 ans'],
            ['id_candidat' => 3, 'id_personne' => 5, 'niveau_etude' => 'Master gestion', 'experience' => '4 ans'],
            ['id_candidat' => 4, 'id_personne' => 6, 'niveau_etude' => 'Licence informatique', 'experience' => '1 an'],
        ]);
        $this->insert($pdo, 'demande_recrutement', [
            ['id_demande' => 1, 'id_poste' => 2, 'id_demandeur' => 3, 'effectif_demande' => 1, 'motif' => 'Renforcement de l’équipe', 'salaire_par_personne' => 1800000, 'date_besoin' => '2025-02-01', 'statut' => 'VALIDE', 'date_creation' => '2025-01-03 09:00:00', 'date_modification' => '2025-01-03 09:00:00'],
        ]);
        $this->insert($pdo, 'offre_emploi', [
            ['id_offre' => 1, 'id_demande' => 1, 'titre' => 'Développeur PHP junior', 'description' => 'Développement CodeIgniter', 'date_publication' => '2025-01-04', 'date_limite' => '2025-01-31', 'statut' => 'FERMEE', 'cree_par' => 3, 'date_creation' => '2025-01-04 09:00:00'],
        ]);
        $this->insert($pdo, 'candidature', [
            ['id_candidature' => 1, 'id_offre' => 1, 'id_candidat' => 1, 'cv_nom' => 'cv-tovo.pdf', 'cv_type_mime' => 'application/pdf', 'cv_donnees' => 'CV de démonstration', 'lettre_nom' => 'lettre.pdf', 'lettre_type_mime' => 'application/pdf', 'lettre_donnees' => 'Lettre de démonstration', 'cin_nom' => 'cin.pdf', 'cin_type_mime' => 'application/pdf', 'cin_donnees' => 'CIN de démonstration', 'motivation' => 'Je souhaite rejoindre votre équipe.', 'statut' => 'RETENUE', 'date_candidature' => '2025-01-05 10:00:00'],
            ['id_candidature' => 2, 'id_offre' => 1, 'id_candidat' => 2, 'cv_nom' => 'cv-lova.pdf', 'cv_type_mime' => 'application/pdf', 'cv_donnees' => 'CV de démonstration', 'lettre_nom' => 'lettre-lova.pdf', 'lettre_type_mime' => 'application/pdf', 'lettre_donnees' => 'Lettre de démonstration', 'cin_nom' => 'cin-lova.pdf', 'cin_type_mime' => 'application/pdf', 'cin_donnees' => 'CIN de démonstration', 'motivation' => 'Candidature de démonstration.', 'statut' => 'RETENUE', 'date_candidature' => '2025-01-06 10:00:00'],
            ['id_candidature' => 3, 'id_offre' => 1, 'id_candidat' => 3, 'cv_nom' => 'cv-soa.pdf', 'cv_type_mime' => 'application/pdf', 'cv_donnees' => 'CV de démonstration', 'lettre_nom' => 'lettre-soa.pdf', 'lettre_type_mime' => 'application/pdf', 'lettre_donnees' => 'Lettre de démonstration', 'cin_nom' => 'cin-soa.pdf', 'cin_type_mime' => 'application/pdf', 'cin_donnees' => 'CIN de démonstration', 'motivation' => 'Candidature de démonstration.', 'statut' => 'RETENUE', 'date_candidature' => '2025-01-07 10:00:00'],
            ['id_candidature' => 4, 'id_offre' => 1, 'id_candidat' => 4, 'cv_nom' => 'cv-faly.pdf', 'cv_type_mime' => 'application/pdf', 'cv_donnees' => 'CV de démonstration', 'lettre_nom' => 'lettre-faly.pdf', 'lettre_type_mime' => 'application/pdf', 'lettre_donnees' => 'Lettre de démonstration', 'cin_nom' => 'cin-faly.pdf', 'cin_type_mime' => 'application/pdf', 'cin_donnees' => 'CIN de démonstration', 'motivation' => 'Candidature de démonstration.', 'statut' => 'RETENUE', 'date_candidature' => '2025-01-08 10:00:00'],
        ]);
        $this->insert($pdo, 'entretien', [
            ['id_entretien' => 1, 'id_candidature' => 1, 'id_organisateur' => 2, 'date_entretien' => '2025-01-10 09:00:00', 'lieu' => 'Salle RH', 'notes' => 'Profil validé', 'statut' => 'REALISE', 'date_creation' => '2025-01-05 11:00:00'],
        ]);
        $this->insert($pdo, 'evaluation_candidat', [
            ['id_evaluation' => 1, 'id_candidature' => 1, 'id_evaluateur' => 2, 'id_entretien' => 1, 'id_test' => null, 'type_evaluation' => 'ENTRETIEN', 'note' => 8, 'commentaire' => 'Bon niveau technique', 'date_evaluation' => '2025-01-10'],
        ]);
        $this->insert($pdo, 'test_technique', [
            ['id_test' => 1, 'id_candidature' => 1, 'id_organisateur' => 2, 'intitule' => 'Test PHP', 'description' => 'API CRUD simple', 'date_test' => '2025-01-08', 'statut' => 'REALISE', 'date_creation' => '2025-01-05 12:00:00'],
        ]);
        $this->insert($pdo, 'contrat', [
            ['id_contrat' => 1, 'id_candidature' => 1, 'reference' => 'CTR-2025-001', 'type_contrat' => 'CDI', 'date_debut' => '2025-02-01', 'date_fin' => null, 'salaire' => 1800000, 'periodicite_salaire' => 'MENSUEL', 'lieu_travail' => 'Antananarivo', 'missions' => 'Développement logiciel', 'horaires' => '08:00-17:00', 'conges_absences' => 'Selon règlement intérieur', 'statut' => 'SIGNE', 'pdf_nom' => 'contrat-CTR-2025-001.pdf', 'pdf_type_mime' => 'application/pdf', 'pdf_donnees' => $this->contratPdf('CTR-2025-001', 'Tovo Andria', 'Développeur logiciel', 1800000), 'date_signature' => '2025-01-15'],
            ['id_contrat' => 2, 'id_candidature' => 2, 'reference' => 'CTR-2025-002', 'type_contrat' => 'CDI', 'date_debut' => '2025-03-01', 'date_fin' => null, 'salaire' => 1900000, 'periodicite_salaire' => 'MENSUEL', 'lieu_travail' => 'Antananarivo', 'missions' => 'Développement logiciel et maintenance', 'horaires' => '08:00-17:00', 'conges_absences' => 'Selon règlement intérieur', 'statut' => 'SIGNE', 'pdf_nom' => 'contrat-CTR-2025-002.pdf', 'pdf_type_mime' => 'application/pdf', 'pdf_donnees' => $this->contratPdf('CTR-2025-002', 'Lova Rabe', 'Développeur logiciel', 1900000), 'date_signature' => '2025-02-15'],
            ['id_contrat' => 3, 'id_candidature' => 3, 'reference' => 'CTR-2025-003', 'type_contrat' => 'CDD', 'date_debut' => '2025-04-01', 'date_fin' => '2026-03-31', 'salaire' => 1600000, 'periodicite_salaire' => 'MENSUEL', 'lieu_travail' => 'Antananarivo', 'missions' => 'Support applicatif et reporting', 'horaires' => '08:00-17:00', 'conges_absences' => 'Selon règlement intérieur', 'statut' => 'SIGNE', 'pdf_nom' => 'contrat-CTR-2025-003.pdf', 'pdf_type_mime' => 'application/pdf', 'pdf_donnees' => $this->contratPdf('CTR-2025-003', 'Soa Rakotoarisoa', 'Chargée de support', 1600000), 'date_signature' => '2025-03-15'],
            ['id_contrat' => 4, 'id_candidature' => 4, 'reference' => 'CTR-2025-004', 'type_contrat' => 'CDI', 'date_debut' => '2025-05-01', 'date_fin' => null, 'salaire' => 1750000, 'periodicite_salaire' => 'MENSUEL', 'lieu_travail' => 'Antananarivo', 'missions' => 'Tests et qualité logicielle', 'horaires' => '08:00-17:00', 'conges_absences' => 'Selon règlement intérieur', 'statut' => 'SIGNE', 'pdf_nom' => 'contrat-CTR-2025-004.pdf', 'pdf_type_mime' => 'application/pdf', 'pdf_donnees' => $this->contratPdf('CTR-2025-004', 'Faly Randria', 'Testeur logiciel', 1750000), 'date_signature' => '2025-04-15'],
        ]);
        $this->insert($pdo, 'employe', [
            ['id_employe' => 1, 'id_contrat' => 1, 'id_personne' => 3, 'matricule' => 'EMP-0001', 'date_embauche' => '2025-02-01', 'statut' => 'ACTIF'],
            ['id_employe' => 2, 'id_contrat' => 2, 'id_personne' => 4, 'matricule' => 'EMP-0002', 'date_embauche' => '2025-03-01', 'statut' => 'ACTIF'],
            ['id_employe' => 3, 'id_contrat' => 3, 'id_personne' => 5, 'matricule' => 'EMP-0003', 'date_embauche' => '2025-04-01', 'statut' => 'ACTIF'],
            ['id_employe' => 4, 'id_contrat' => 4, 'id_personne' => 6, 'matricule' => 'EMP-0004', 'date_embauche' => '2025-05-01', 'statut' => 'ACTIF'],
        ]);
        $this->insert($pdo, 'affectation', [
            ['id_affectation' => 1, 'id_employe' => 1, 'id_departement' => 2, 'date_debut' => '2025-02-01', 'date_fin' => null, 'fonction' => 'Développeur logiciel'],
            ['id_affectation' => 2, 'id_employe' => 2, 'id_departement' => 2, 'date_debut' => '2025-03-01', 'date_fin' => null, 'fonction' => 'Développeur logiciel'],
            ['id_affectation' => 3, 'id_employe' => 3, 'id_departement' => 1, 'date_debut' => '2025-04-01', 'date_fin' => null, 'fonction' => 'Chargée de support'],
            ['id_affectation' => 4, 'id_employe' => 4, 'id_departement' => 2, 'date_debut' => '2025-05-01', 'date_fin' => null, 'fonction' => 'Testeur logiciel'],
        ]);
        $this->insert($pdo, 'historique_salaire', [
            ['id_historique' => 1, 'id_employe' => 1, 'ancien_salaire' => null, 'nouveau_salaire' => 1800000, 'periodicite_salaire' => 'MENSUEL', 'motif' => 'Embauche', 'id_utilisateur' => 2, 'date_effet' => '2025-02-01', 'date_action' => '2025-02-01 08:00:00'],
            ['id_historique' => 2, 'id_employe' => 2, 'ancien_salaire' => null, 'nouveau_salaire' => 1900000, 'periodicite_salaire' => 'MENSUEL', 'motif' => 'Embauche', 'id_utilisateur' => 2, 'date_effet' => '2025-03-01', 'date_action' => '2025-03-01 08:00:00'],
            ['id_historique' => 3, 'id_employe' => 3, 'ancien_salaire' => null, 'nouveau_salaire' => 1600000, 'periodicite_salaire' => 'MENSUEL', 'motif' => 'Embauche', 'id_utilisateur' => 2, 'date_effet' => '2025-04-01', 'date_action' => '2025-04-01 08:00:00'],
            ['id_historique' => 4, 'id_employe' => 4, 'ancien_salaire' => null, 'nouveau_salaire' => 1750000, 'periodicite_salaire' => 'MENSUEL', 'motif' => 'Embauche', 'id_utilisateur' => 2, 'date_effet' => '2025-05-01', 'date_action' => '2025-05-01 08:00:00'],
        ]);
        $this->insert($pdo, 'document_employe', [
            ['id_document' => 1, 'id_employe' => 1, 'type_document' => 'CONTRAT', 'nom_fichier' => 'contrat-CTR-2025-001.pdf', 'type_mime' => 'application/pdf', 'donnees' => $this->contratPdf('CTR-2025-001', 'Tovo Andria', 'Développeur logiciel', 1800000), 'date_expiration' => null, 'commentaire' => 'Contrat signé', 'id_utilisateur' => 2, 'date_creation' => '2025-02-01 08:00:00', 'date_modification' => '2025-02-01 08:00:00'],
            ['id_document' => 2, 'id_employe' => 2, 'type_document' => 'CONTRAT', 'nom_fichier' => 'contrat-CTR-2025-002.pdf', 'type_mime' => 'application/pdf', 'donnees' => $this->contratPdf('CTR-2025-002', 'Lova Rabe', 'Développeur logiciel', 1900000), 'date_expiration' => null, 'commentaire' => 'Contrat signé', 'id_utilisateur' => 2, 'date_creation' => '2025-03-01 08:00:00', 'date_modification' => '2025-03-01 08:00:00'],
            ['id_document' => 3, 'id_employe' => 3, 'type_document' => 'CONTRAT', 'nom_fichier' => 'contrat-CTR-2025-003.pdf', 'type_mime' => 'application/pdf', 'donnees' => $this->contratPdf('CTR-2025-003', 'Soa Rakotoarisoa', 'Chargée de support', 1600000), 'date_expiration' => '2026-03-31', 'commentaire' => 'CDD signé', 'id_utilisateur' => 2, 'date_creation' => '2025-04-01 08:00:00', 'date_modification' => '2025-04-01 08:00:00'],
            ['id_document' => 4, 'id_employe' => 4, 'type_document' => 'CONTRAT', 'nom_fichier' => 'contrat-CTR-2025-004.pdf', 'type_mime' => 'application/pdf', 'donnees' => $this->contratPdf('CTR-2025-004', 'Faly Randria', 'Testeur logiciel', 1750000), 'date_expiration' => null, 'commentaire' => 'Contrat signé', 'id_utilisateur' => 2, 'date_creation' => '2025-05-01 08:00:00', 'date_modification' => '2025-05-01 08:00:00'],
        ]);
        $this->insert($pdo, 'regime_travail', [
            ['id_regime' => 1, 'libelle' => 'Temps plein', 'heures_hebdomadaires' => 40, 'actif' => 1],
            ['id_regime' => 2, 'libelle' => 'Temps partiel', 'heures_hebdomadaires' => 20, 'actif' => 1],
        ]);
        $this->insert($pdo, 'horaire', [
            ['id_horaire' => 1, 'id_regime' => 1, 'libelle' => 'Horaire standard', 'heure_debut' => '08:00', 'heure_fin' => '17:00', 'pause_minutes' => 60, 'actif' => 1],
        ]);
        $this->insert($pdo, 'planning', [
            ['id_planning' => 1, 'id_employe' => 1, 'id_horaire' => 1, 'date_planning' => '2025-02-10', 'statut' => 'PLANIFIE'],
        ]);
        $this->insert($pdo, 'pointage', [
            ['id_pointage' => 1, 'id_employe' => 1, 'date_pointage' => '2025-02-10', 'heure_entree' => '2025-02-10 08:12:00', 'heure_sortie' => '2025-02-10 18:00:00', 'source' => 'MANUEL', 'statut' => 'CLOTURE', 'commentaire' => 'Pointage de démonstration'],
            ['id_pointage' => 2, 'id_employe' => 1, 'date_pointage' => '2025-02-11', 'heure_entree' => '2025-02-11 08:00:00', 'heure_sortie' => null, 'source' => 'MANUEL', 'statut' => 'OUVERT', 'commentaire' => null],
        ]);
        $this->insert($pdo, 'retard', [
            ['id_retard' => 1, 'id_pointage' => 1, 'minutes' => 12, 'motif' => 'Transport', 'justifie' => 0, 'statut' => 'A_TRAITER'],
        ]);
        $this->insert($pdo, 'heure_supplementaire', [
            ['id_heure_supplementaire' => 1, 'id_employe' => 1, 'date_travail' => '2025-02-10', 'heures' => 1, 'motif' => 'Livraison urgente', 'statut' => 'VALIDE', 'id_validateur' => 2],
        ]);
        $this->insert($pdo, 'temps_travaille', [
            ['id_temps' => 1, 'id_pointage' => 1, 'minutes_travaillees' => 468, 'minutes_theoriques' => 480, 'ecart_minutes' => -12, 'calcule_le' => '2025-02-10 18:05:00'],
        ]);
        $this->insert($pdo, 'anomalie_pointage', [
            ['id_anomalie' => 1, 'id_pointage' => 2, 'id_employe' => 1, 'date_anomalie' => '2025-02-11', 'type_anomalie' => 'SORTIE_MANQUANTE', 'description' => 'Heure de sortie absente', 'statut' => 'OUVERTE', 'date_resolution' => null],
        ]);
        $this->insert($pdo, 'type_conge', [
            ['id_type_conge' => 1, 'code' => 'CA', 'libelle' => 'Congé annuel', 'description' => 'Congé payé annuel', 'jours_annuels' => 30, 'justificatif_obligatoire' => 0, 'actif' => 1],
            ['id_type_conge' => 2, 'code' => 'MAL', 'libelle' => 'Congé maladie', 'description' => 'Absence pour maladie', 'jours_annuels' => 0, 'justificatif_obligatoire' => 1, 'actif' => 1],
        ]);
        $this->insert($pdo, 'droit_conge', [
            ['id_droit_conge' => 1, 'id_type_conge' => 1, 'code_role' => 'EMPLOYE', 'anciennete_mois' => 0, 'jours_annuels' => 30],
            ['id_droit_conge' => 2, 'id_type_conge' => 2, 'code_role' => 'EMPLOYE', 'anciennete_mois' => 0, 'jours_annuels' => 0],
        ]);
        $this->insert($pdo, 'solde_conge', [
            ['id_solde_conge' => 1, 'id_employe' => 1, 'id_type_conge' => 1, 'annee' => $year, 'acquis' => 30, 'pris' => 2, 'ajuste' => 0],
            ['id_solde_conge' => 2, 'id_employe' => 1, 'id_type_conge' => 2, 'annee' => $year, 'acquis' => 0, 'pris' => 0, 'ajuste' => 0],
        ]);
        $this->insert($pdo, 'demande_conge', [
            ['id_demande_conge' => 1, 'id_employe' => 1, 'id_type_conge' => 1, 'date_debut' => "$year-03-10", 'date_fin' => "$year-03-11", 'nombre_jours' => 2, 'motif' => 'Repos', 'statut' => 'VALIDE', 'id_validateur' => 2, 'commentaire_validation' => 'Accord RH', 'date_creation' => "$year-02-20 09:00:00", 'date_decision' => "$year-02-21 09:00:00"],
            ['id_demande_conge' => 2, 'id_employe' => 1, 'id_type_conge' => 1, 'date_debut' => "$year-04-15", 'date_fin' => "$year-04-17", 'nombre_jours' => 3, 'motif' => 'Projet personnel', 'statut' => 'EN_ATTENTE', 'id_validateur' => null, 'commentaire_validation' => null, 'date_creation' => "$year-03-01 09:00:00", 'date_decision' => null],
        ]);
        $this->insert($pdo, 'justificatif_conge', [
            ['id_justificatif' => 1, 'id_demande_conge' => 1, 'nom_fichier' => 'demande-conge.pdf', 'type_mime' => 'application/pdf', 'donnees' => 'Justificatif de démonstration', 'date_depot' => "$year-02-20 09:00:00"],
        ]);
        $this->insert($pdo, 'absence', [
            ['id_absence' => 1, 'id_employe' => 1, 'date_debut' => "$year-05-05", 'date_fin' => "$year-05-05", 'type_absence' => 'MALADIE', 'motif' => 'Consultation médicale', 'justifiee' => 1, 'id_justificatif' => 1, 'cree_par' => 2, 'date_creation' => "$year-05-05 10:00:00"],
        ]);
        $this->insert($pdo, 'journal_acces', [
            ['id_journal' => 1, 'id_utilisateur' => 2, 'id_employe' => 1, 'action' => 'CONSULTATION', 'ressource' => 'dossier_employe', 'adresse_ip' => '127.0.0.1', 'details' => 'Consultation du dossier de démonstration', 'date_action' => "$year-02-01 08:10:00"],
        ]);
        $this->insert($pdo, 'notification', [
            ['id_notification' => 1, 'id_utilisateur' => 2, 'titre' => 'Nouvelle demande de congé', 'message' => 'Une demande est en attente de validation.', 'lien' => '/rh/conges', 'lue' => 0, 'date_creation' => "$year-03-01 09:01:00", 'date_lecture' => null],
        ]);
        $this->insert($pdo, 'utilisateur_departement', [['id_utilisateur' => 4, 'id_departement' => 2]]);
        $this->insert($pdo, 'condition_contrat', [
            ['id_condition' => 1, 'id_poste' => 2, 'libelle' => 'Période d’essai', 'type_valeur' => 'TEXTE', 'valeur_condition' => '3 mois', 'ordre_affichage' => 1, 'statut' => 'CONFIRMER', 'modifie_par' => 2, 'date_creation' => "$year-01-02 09:00:00", 'date_modification' => "$year-01-02 09:00:00"],
        ]);
        $this->insert($pdo, 'contrat_condition', [['id_contrat_condition' => 1, 'id_contrat' => 1, 'libelle' => 'Période d’essai', 'type_valeur' => 'TEXTE', 'valeur_finale' => '3 mois', 'ordre_affichage' => 1]]);
        $this->insert($pdo, 'requete_condition_contrat', [['id_requete' => 1, 'id_candidature' => 1, 'id_condition' => 1, 'valeur_proposee' => '3 mois', 'commentaire_candidat' => 'Accepté', 'statut' => 'ACCEPTEE_RH', 'date_creation' => '2025-01-06 09:00:00', 'date_modification' => '2025-01-06 09:00:00']]);
        $this->insert($pdo, 'validation_demande', [['id_validation' => 1, 'id_demande' => 1, 'id_validateur' => 3, 'type_validation' => 'DIRECTION', 'decision' => 'VALIDE', 'commentaire' => 'Besoin confirmé', 'date_decision' => '2025-01-03 10:00:00']]);
        $this->insert($pdo, 'validation_offre', [['id_validation_offre' => 1, 'id_offre' => 1, 'id_validateur' => 3, 'decision' => 'VALIDE', 'commentaire' => 'Publication autorisée', 'date_decision' => '2025-01-04 09:00:00']]);
        $this->insert($pdo, 'historique_candidature', [['id_historique' => 1, 'id_candidature' => 1, 'ancien_statut' => 'SOUMISE', 'nouveau_statut' => 'ACCEPTEE', 'id_utilisateur' => 2, 'commentaire' => 'Candidat retenu', 'date_action' => '2025-01-15 09:00:00']]);
        $this->insert($pdo, 'historique_demande', [['id_historique' => 1, 'id_demande' => 1, 'ancien_statut' => 'BROUILLON', 'nouveau_statut' => 'VALIDE', 'id_utilisateur' => 3, 'commentaire' => 'Demande approuvée', 'date_action' => '2025-01-03 10:00:00']]);
        $this->insert($pdo, 'historique_offre_emploi', [['id_historique' => 1, 'id_offre' => 1, 'ancien_statut' => 'BROUILLON', 'nouveau_statut' => 'FERMEE', 'id_utilisateur' => 2, 'commentaire' => 'Recrutement terminé', 'date_action' => '2025-02-01 08:00:00']]);
        $this->insert($pdo, 'historique_poste', [['id_historique' => 1, 'id_poste' => 2, 'ancien_statut' => 'INACTIF', 'nouveau_statut' => 'ACTIF', 'id_utilisateur' => 3, 'commentaire' => 'Poste ouvert', 'date_action' => '2025-01-03 09:00:00']]);
        $this->insert($pdo, 'historique_entretien', [['id_historique' => 1, 'id_entretien' => 1, 'ancien_statut' => 'PLANIFIE', 'nouveau_statut' => 'REALISE', 'id_utilisateur' => 2, 'commentaire' => 'Entretien réalisé', 'date_action' => '2025-01-10 12:00:00']]);
        $this->insert($pdo, 'historique_test_technique', [['id_historique' => 1, 'id_test' => 1, 'ancien_statut' => 'PLANIFIE', 'nouveau_statut' => 'REALISE', 'id_utilisateur' => 2, 'commentaire' => 'Test corrigé', 'date_action' => '2025-01-08 17:00:00']]);
        $this->insert($pdo, 'historique_condition_contrat', [['id_historique' => 1, 'id_condition' => 1, 'ancienne_valeur' => null, 'nouvelle_valeur' => '3 mois', 'ancien_statut' => 'CREER', 'nouveau_statut' => 'CONFIRMER', 'id_utilisateur' => 2, 'commentaire' => null, 'date_action' => '2025-01-06 09:00:00']]);
        $this->insert($pdo, 'historique_contrat', [['id_historique' => 1, 'id_contrat' => 1, 'ancien_statut' => 'BROUILLON', 'nouveau_statut' => 'SIGNE', 'id_utilisateur' => 2, 'commentaire' => 'Contrat signé', 'date_action' => '2025-02-01 08:00:00']]);
        $this->insert($pdo, 'historique_employe', [['id_historique' => 1, 'id_employe' => 1, 'ancien_statut' => 'INACTIF', 'nouveau_statut' => 'ACTIF', 'id_utilisateur' => 2, 'commentaire' => 'Embauche confirmée', 'date_action' => '2025-02-01 08:00:00']]);
        $this->insert($pdo, 'historique_requete_condition', [['id_historique' => 1, 'id_requete' => 1, 'ancien_statut' => 'SOUMISE', 'nouveau_statut' => 'ACCEPTEE_RH', 'id_utilisateur' => 2, 'commentaire' => null, 'date_action' => '2025-01-06 09:00:00']]);
        $this->insert($pdo, 'historique_formulaire_reponse', [['id_historique' => 1, 'id_utilisateur' => 2, 'type_formulaire' => 'employe', 'id_enregistrement' => 1, 'donnees' => '{"statut":"ACTIF"}', 'action' => 'CREATION', 'date_action' => '2025-02-01 08:00:00']]);

        $this->insert($pdo, 'parametre_paie', [
            ['id_parametre' => 1, 'code' => 'SME', 'valeur' => 350000, 'secteur' => 'NON_AGRICOLE', 'date_effet' => '2025-01-01', 'date_fin' => null],
            ['id_parametre' => 2, 'code' => 'PLAFOND_CNAPS', 'valeur' => 2800000, 'secteur' => 'NON_AGRICOLE', 'date_effet' => '2025-01-01', 'date_fin' => null],
            ['id_parametre' => 3, 'code' => 'DIVISEUR_HEURES', 'valeur' => 173.33, 'secteur' => 'NON_AGRICOLE', 'date_effet' => '2025-01-01', 'date_fin' => null],
            ['id_parametre' => 4, 'code' => 'DIVISEUR_HEURES', 'valeur' => 200, 'secteur' => 'AGRICOLE', 'date_effet' => '2025-01-01', 'date_fin' => null],
            ['id_parametre' => 5, 'code' => 'CNAPS_SALARIAL', 'valeur' => 1, 'secteur' => 'NON_AGRICOLE', 'date_effet' => '2025-01-01', 'date_fin' => null],
            ['id_parametre' => 6, 'code' => 'CNAPS_PATRONAL', 'valeur' => 13, 'secteur' => 'NON_AGRICOLE', 'date_effet' => '2025-01-01', 'date_fin' => null],
            ['id_parametre' => 7, 'code' => 'OSTIE_SALARIAL', 'valeur' => 1, 'secteur' => 'NON_AGRICOLE', 'date_effet' => '2025-01-01', 'date_fin' => null],
            ['id_parametre' => 8, 'code' => 'OSTIE_PATRONAL', 'valeur' => 5, 'secteur' => 'NON_AGRICOLE', 'date_effet' => '2025-01-01', 'date_fin' => null],
            ['id_parametre' => 9, 'code' => 'MULT_PLAFOND_CNAPS', 'valeur' => 8, 'secteur' => 'NON_AGRICOLE', 'date_effet' => '2025-01-01', 'date_fin' => null],
            ['id_parametre' => 10, 'code' => 'MULT_PLAFOND_CNAPS', 'valeur' => 8, 'secteur' => 'AGRICOLE', 'date_effet' => '2025-01-01', 'date_fin' => null],
            ['id_parametre' => 11, 'code' => 'DIVISEUR_JOURS', 'valeur' => 30, 'secteur' => 'NON_AGRICOLE', 'date_effet' => '2025-01-01', 'date_fin' => null],
            ['id_parametre' => 12, 'code' => 'DIVISEUR_JOURS', 'valeur' => 30, 'secteur' => 'AGRICOLE', 'date_effet' => '2025-01-01', 'date_fin' => null],
            ['id_parametre' => 13, 'code' => 'HEURES_JOUR', 'valeur' => 8, 'secteur' => 'NON_AGRICOLE', 'date_effet' => '2025-01-01', 'date_fin' => null],
            ['id_parametre' => 14, 'code' => 'HEURES_JOUR', 'valeur' => 8, 'secteur' => 'AGRICOLE', 'date_effet' => '2025-01-01', 'date_fin' => null],
            ['id_parametre' => 15, 'code' => 'SEUIL_HS_1', 'valeur' => 12, 'secteur' => 'NON_AGRICOLE', 'date_effet' => '2025-01-01', 'date_fin' => null],
            ['id_parametre' => 16, 'code' => 'PLAFOND_HS', 'valeur' => 20, 'secteur' => 'NON_AGRICOLE', 'date_effet' => '2025-01-01', 'date_fin' => null],
            ['id_parametre' => 17, 'code' => 'COEF_HS_1', 'valeur' => 1.30, 'secteur' => 'NON_AGRICOLE', 'date_effet' => '2025-01-01', 'date_fin' => null],
            ['id_parametre' => 18, 'code' => 'COEF_HS_2', 'valeur' => 1.50, 'secteur' => 'NON_AGRICOLE', 'date_effet' => '2025-01-01', 'date_fin' => null],
            ['id_parametre' => 19, 'code' => 'COEF_DIMANCHE', 'valeur' => 2, 'secteur' => 'NON_AGRICOLE', 'date_effet' => '2025-01-01', 'date_fin' => null],
            ['id_parametre' => 20, 'code' => 'COEF_NUIT', 'valeur' => 1.5, 'secteur' => 'NON_AGRICOLE', 'date_effet' => '2025-01-01', 'date_fin' => null],
            ['id_parametre' => 21, 'code' => 'CNAPS_SALARIAL', 'valeur' => 6, 'secteur' => 'AGRICOLE', 'date_effet' => '2025-01-01', 'date_fin' => null],
            ['id_parametre' => 22, 'code' => 'CNAPS_PATRONAL', 'valeur' => 8, 'secteur' => 'AGRICOLE', 'date_effet' => '2025-01-01', 'date_fin' => null],
            ['id_parametre' => 23, 'code' => 'OSTIE_SALARIAL', 'valeur' => 1, 'secteur' => 'AGRICOLE', 'date_effet' => '2025-01-01', 'date_fin' => null],
            ['id_parametre' => 24, 'code' => 'OSTIE_PATRONAL', 'valeur' => 5, 'secteur' => 'AGRICOLE', 'date_effet' => '2025-01-01', 'date_fin' => null],
            ['id_parametre' => 25, 'code' => 'SEUIL_HS_1', 'valeur' => 12, 'secteur' => 'AGRICOLE', 'date_effet' => '2025-01-01', 'date_fin' => null],
            ['id_parametre' => 26, 'code' => 'PLAFOND_HS', 'valeur' => 20, 'secteur' => 'AGRICOLE', 'date_effet' => '2025-01-01', 'date_fin' => null],
            ['id_parametre' => 27, 'code' => 'COEF_HS_1', 'valeur' => 1.30, 'secteur' => 'AGRICOLE', 'date_effet' => '2025-01-01', 'date_fin' => null],
            ['id_parametre' => 28, 'code' => 'COEF_HS_2', 'valeur' => 1.50, 'secteur' => 'AGRICOLE', 'date_effet' => '2025-01-01', 'date_fin' => null],
            ['id_parametre' => 29, 'code' => 'COEF_DIMANCHE', 'valeur' => 2, 'secteur' => 'AGRICOLE', 'date_effet' => '2025-01-01', 'date_fin' => null],
            ['id_parametre' => 30, 'code' => 'COEF_NUIT', 'valeur' => 1.5, 'secteur' => 'AGRICOLE', 'date_effet' => '2025-01-01', 'date_fin' => null],
            ['id_parametre' => 31, 'code' => 'SME', 'valeur' => 350000, 'secteur' => 'AGRICOLE', 'date_effet' => '2025-01-01', 'date_fin' => null],
        ]);
        $this->insert($pdo, 'bareme_irsa', [
            ['id_bareme' => 1, 'borne_inf' => 0, 'borne_sup' => 350000, 'taux' => 0, 'date_effet' => '2000-01-01', 'date_fin' => null],
            ['id_bareme' => 2, 'borne_inf' => 350000, 'borne_sup' => 400000, 'taux' => 0.05, 'date_effet' => '2000-01-01', 'date_fin' => null],
            ['id_bareme' => 3, 'borne_inf' => 400000, 'borne_sup' => 500000, 'taux' => 0.10, 'date_effet' => '2000-01-01', 'date_fin' => null],
            ['id_bareme' => 4, 'borne_inf' => 500000, 'borne_sup' => 600000, 'taux' => 0.15, 'date_effet' => '2000-01-01', 'date_fin' => null],
            ['id_bareme' => 5, 'borne_inf' => 600000, 'borne_sup' => 4000000, 'taux' => 0.20, 'date_effet' => '2000-01-01', 'date_fin' => null],
            ['id_bareme' => 6, 'borne_inf' => 4000000, 'borne_sup' => null, 'taux' => 0.25, 'date_effet' => '2000-01-01', 'date_fin' => null],
        ]);
        $this->insert($pdo, 'rubrique_paie', [
            ['id_rubrique' => 1, 'code' => 'INDEMNITE', 'libelle' => 'Indemnité', 'nature' => 'GAIN', 'imposable' => 1, 'actif' => 1],
            ['id_rubrique' => 2, 'code' => 'MAJORATION', 'libelle' => 'Heures majorées', 'nature' => 'GAIN', 'imposable' => 1, 'actif' => 1],
            ['id_rubrique' => 3, 'code' => 'RETENUE', 'libelle' => 'Avance ou acompte', 'nature' => 'RETENUE', 'imposable' => 0, 'actif' => 1],
        ]);
        $this->insert($pdo, 'periode_paie', [
            ['id_periode' => 1, 'mois' => 10, 'annee' => $year, 'date_paiement' => "$year-10-31", 'statut' => 'VALIDE'],
            ['id_periode' => 2, 'mois' => 11, 'annee' => $year, 'date_paiement' => "$year-11-30", 'statut' => 'OUVERT'],
        ]);
        $this->insert($pdo, 'variable_paie', [
            ['id_variable' => 1, 'id_employe' => 1, 'id_periode' => 2, 'id_rubrique' => 1, 'nombre' => 1, 'montant' => 150000, 'motif' => 'Indemnité de transport'],
            ['id_variable' => 2, 'id_employe' => 1, 'id_periode' => 2, 'id_rubrique' => 2, 'nombre' => 5, 'montant' => 55000, 'motif' => 'Heures supplémentaires majorées'],
            ['id_variable' => 3, 'id_employe' => 2, 'id_periode' => 2, 'id_rubrique' => 1, 'nombre' => 1, 'montant' => 100000, 'motif' => 'Indemnité logement'],
            ['id_variable' => 4, 'id_employe' => 3, 'id_periode' => 2, 'id_rubrique' => 3, 'nombre' => 1, 'montant' => 50000, 'motif' => 'Acompte'],
        ]);

        $this->insert($pdo, 'competence', [
            ['id_competence' => 1, 'code' => 'PHP', 'libelle' => 'Développement PHP', 'description' => 'Maîtrise du développement PHP', 'actif' => 1],
            ['id_competence' => 2, 'code' => 'SQL', 'libelle' => 'Bases de données SQL', 'description' => 'Modélisation et requêtes SQL', 'actif' => 1],
            ['id_competence' => 3, 'code' => 'COMM', 'libelle' => 'Communication professionnelle', 'description' => 'Communication écrite et orale', 'actif' => 1],
        ]);
        $this->insert($pdo, 'poste_competence', [
            ['id_poste_competence' => 1, 'id_poste' => 2, 'id_competence' => 1, 'niveau_attendu' => 4],
            ['id_poste_competence' => 2, 'id_poste' => 2, 'id_competence' => 2, 'niveau_attendu' => 4],
            ['id_poste_competence' => 3, 'id_poste' => 1, 'id_competence' => 3, 'niveau_attendu' => 5],
        ]);
        $this->insert($pdo, 'employe_competence', [
            ['id_employe_competence' => 1, 'id_employe' => 1, 'id_competence' => 1, 'niveau_acquis' => 3, 'date_evaluation' => '2025-06-30', 'id_evaluateur' => 2],
            ['id_employe_competence' => 2, 'id_employe' => 1, 'id_competence' => 2, 'niveau_acquis' => 4, 'date_evaluation' => '2025-06-30', 'id_evaluateur' => 2],
            ['id_employe_competence' => 3, 'id_employe' => 2, 'id_competence' => 1, 'niveau_acquis' => 5, 'date_evaluation' => '2025-06-30', 'id_evaluateur' => 2],
        ]);
        $this->insert($pdo, 'objectif', [
            ['id_objectif' => 1, 'id_employe' => 1, 'titre' => 'Livrer le module de paie', 'description' => 'Implémenter et tester les calculs mensuels', 'date_debut' => "$year-01-01", 'date_fin' => "$year-06-30", 'resultat' => 'Livraison validée', 'statut' => 'ATTEINT'],
            ['id_objectif' => 2, 'id_employe' => 2, 'titre' => 'Réduire les anomalies de production', 'description' => 'Mettre en place les contrôles qualité', 'date_debut' => "$year-01-01", 'date_fin' => "$year-12-31", 'resultat' => null, 'statut' => 'EN_COURS'],
        ]);
        $this->insert($pdo, 'evaluation_performance', [
            ['id_evaluation_performance' => 1, 'id_employe' => 1, 'id_evaluateur' => 2, 'periode' => 'S1-'.$year, 'note_globale' => 14.5, 'commentaire' => 'Bon niveau avec besoin de progression PHP', 'statut' => 'VALIDE', 'date_evaluation' => "$year-07-01"],
            ['id_evaluation_performance' => 2, 'id_employe' => 2, 'id_evaluateur' => 2, 'periode' => 'S1-'.$year, 'note_globale' => 16.0, 'commentaire' => 'Très bon niveau technique', 'statut' => 'VALIDE', 'date_evaluation' => "$year-07-01"],
        ]);
        $this->insert($pdo, 'formation', [
            ['id_formation' => 1, 'titre' => 'PHP avancé et sécurité', 'description' => 'Bonne pratique PHP, tests et sécurité applicative', 'id_competence' => 1, 'date_debut' => "$year-08-10", 'date_fin' => "$year-08-12", 'prestataire' => 'Institut de formation numérique', 'cout' => 450000, 'statut' => 'TERMINEE'],
            ['id_formation' => 2, 'titre' => 'Communication professionnelle', 'description' => 'Communication avec les équipes et les partenaires', 'id_competence' => 3, 'date_debut' => "$year-09-15", 'date_fin' => "$year-09-16", 'prestataire' => 'Centre RH Madagascar', 'cout' => 300000, 'statut' => 'PLANIFIEE'],
        ]);
        $this->insert($pdo, 'formation_participant', [
            ['id_participant' => 1, 'id_formation' => 1, 'id_employe' => 1, 'statut' => 'TERMINE', 'niveau_avant' => 3, 'niveau_apres' => 4],
            ['id_participant' => 2, 'id_formation' => 2, 'id_employe' => 3, 'statut' => 'INSCRIT', 'niveau_avant' => 3, 'niveau_apres' => null],
        ]);

        $this->insert($pdo, 'mouvement_carriere', [
            ['id_mouvement' => 1, 'id_employe' => 1, 'type_mouvement' => 'AFFECTATION', 'ancien_poste' => null, 'nouveau_poste' => 'Développeur logiciel', 'ancien_salaire' => null, 'nouveau_salaire' => 1800000, 'id_departement' => 2, 'date_effet' => '2025-02-01', 'date_fin' => null, 'motif' => 'Embauche', 'id_auteur' => 2],
            ['id_mouvement' => 2, 'id_employe' => 1, 'type_mouvement' => 'REVISION_SALAIRE', 'ancien_poste' => 'Développeur logiciel', 'nouveau_poste' => 'Développeur logiciel confirmé', 'ancien_salaire' => 1800000, 'nouveau_salaire' => 1950000, 'id_departement' => 2, 'date_effet' => '2026-01-01', 'date_fin' => null, 'motif' => 'Révision annuelle', 'id_auteur' => 2],
            ['id_mouvement' => 3, 'id_employe' => 3, 'type_mouvement' => 'PROMOTION', 'ancien_poste' => 'Assistante support', 'nouveau_poste' => 'Chargée de support', 'ancien_salaire' => 1500000, 'nouveau_salaire' => 1600000, 'id_departement' => 1, 'date_effet' => '2025-10-01', 'date_fin' => null, 'motif' => 'Promotion', 'id_auteur' => 2],
        ]);

        $this->insert($pdo, 'incident', [
            ['id_incident' => 1, 'id_employe' => 4, 'date_incident' => "$year-10-02", 'objet' => 'Absence répétée non justifiée', 'description' => 'Absences à contrôler avec le règlement intérieur', 'statut' => 'REPONSE', 'id_signaleur' => 2],
        ]);
        $this->insert($pdo, 'demande_explication', [
            ['id_demande_explication' => 1, 'id_incident' => 1, 'date_demande' => "$year-10-03", 'date_limite' => "$year-10-08", 'texte' => 'Merci de fournir vos explications écrites dans le délai prévu.', 'date_notification' => "$year-10-03 09:00:00"],
        ]);
        $this->insert($pdo, 'reponse_explication', [
            ['id_reponse' => 1, 'id_incident' => 1, 'date_reponse' => "$year-10-07", 'texte' => 'Absences liées à un problème de transport et justificatifs joints ultérieurement.', 'piece_jointe_nom' => null, 'piece_jointe_mime' => null, 'piece_jointe_donnees' => null],
        ]);

        $this->insert($pdo, 'rupture_contrat', [
            ['id_rupture' => 1, 'id_employe' => 3, 'motif' => 'FIN_CDD', 'date_notification' => '2026-03-01', 'date_sortie' => '2026-03-31', 'duree_preavis_jours' => 30, 'motif_detail' => 'Terme normal du contrat à durée déterminée', 'statut' => 'EN_COURS', 'id_auteur' => 2],
        ]);
        $this->insert($pdo, 'solde_tout_compte', [
            ['id_stc' => 1, 'id_rupture' => 1, 'dernier_salaire' => 1600000, 'salaire_proratise' => 1600000, 'indemnite_conges' => 250000, 'indemnite_rupture' => 0, 'avances' => 50000, 'retenues' => 0, 'montant_net' => 1800000, 'statut' => 'BROUILLON'],
        ]);
        $this->insert($pdo, 'checklist_offboarding', [
            ['id_checklist' => 1, 'id_rupture' => 1, 'element' => 'STC', 'valeur_statut' => 'EN_COURS', 'date_echeance' => '2026-03-31', 'date_realisation' => null, 'commentaire' => 'Calcul à valider'],
            ['id_checklist' => 2, 'id_rupture' => 1, 'element' => 'Dernier bulletin', 'valeur_statut' => 'A_FAIRE', 'date_echeance' => '2026-03-31', 'date_realisation' => null, 'commentaire' => null],
            ['id_checklist' => 3, 'id_rupture' => 1, 'element' => 'Certificat de travail', 'valeur_statut' => 'A_FAIRE', 'date_echeance' => '2026-03-31', 'date_realisation' => null, 'commentaire' => null],
            ['id_checklist' => 4, 'id_rupture' => 1, 'element' => 'Attestations sociales', 'valeur_statut' => 'A_FAIRE', 'date_echeance' => '2026-03-31', 'date_realisation' => null, 'commentaire' => null],
            ['id_checklist' => 5, 'id_rupture' => 1, 'element' => 'Restitution matériel', 'valeur_statut' => 'A_FAIRE', 'date_echeance' => '2026-03-31', 'date_realisation' => null, 'commentaire' => null],
            ['id_checklist' => 6, 'id_rupture' => 1, 'element' => 'Coupure des accès', 'valeur_statut' => 'A_FAIRE', 'date_echeance' => '2026-03-31', 'date_realisation' => null, 'commentaire' => null],
            ['id_checklist' => 7, 'id_rupture' => 1, 'element' => 'Archivage du dossier', 'valeur_statut' => 'A_FAIRE', 'date_echeance' => '2026-04-15', 'date_realisation' => null, 'commentaire' => null],
        ]);
        $this->insert($pdo, 'attestation_travail', [
            ['id_attestation' => 1, 'id_rupture' => 1, 'type_attestation' => 'CERTIFICAT_TRAVAIL', 'reference' => 'CT-2026-001', 'pdf_nom' => null, 'pdf_type_mime' => null, 'pdf_donnees' => null],
        ]);
        $this->insert($pdo, 'audit_rh', [
            ['id_audit' => 1, 'id_utilisateur' => 2, 'action' => 'CREATE', 'objet' => 'rupture_contrat', 'objet_id' => 1, 'ancienne_valeur' => null, 'nouvelle_valeur' => 'FIN_CDD', 'motif' => 'Création du dossier de départ', 'adresse_ip' => '127.0.0.1'],
        ]);
    }

    private function contratPdf(string $reference, string $employee, string $position, int $salary): string
    {
        $escape = static fn (string $value): string => str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
        $lines = [
            'CONTRAT DE TRAVAIL',
            'Reference : ' . $reference,
            'Salarie : ' . $employee,
            'Poste : ' . $position,
            'Type : Contrat de travail',
            'Remuneration : ' . number_format($salary, 0, ',', ' ') . ' Ar / mois',
            'Document de demonstration SIRH.',
        ];
        $content = "BT\n/F1 14 Tf\n50 760 Td\n";
        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $content .= "0 -28 Td\n";
            }
            $content .= '(' . $escape($line) . ") Tj\n";
        }
        $content .= "ET\n";

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "endstream",
        ];
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $number => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($number + 1) . " 0 obj\n" . $object . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        for ($number = 1; $number <= count($objects); $number++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$number]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF\n";

        return $pdf;
    }

    private function insert(\PDO $pdo, string $table, array $rows): void
    {
        if ($rows === []) {
            return;
        }
        $columns = array_keys($rows[0]);
        $fields = implode(', ', array_map(static fn (string $column): string => '"' . $column . '"', $columns));
        $parameters = implode(', ', array_fill(0, count($columns), '?'));
        $statement = $pdo->prepare("INSERT INTO \"$table\" ($fields) VALUES ($parameters)");
        foreach ($rows as $row) {
            $statement->execute(array_map(static fn (string $column): mixed => $row[$column] ?? null, $columns));
        }
    }

    private function clearTables(\PDO $pdo): void
    {
        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'")->fetchAll(\PDO::FETCH_COLUMN);
        $pdo->exec('PRAGMA foreign_keys = OFF');
        foreach ($tables as $table) {
            $pdo->exec('DELETE FROM "' . str_replace('"', '""', (string) $table) . '"');
        }
        if ($pdo->query("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'sqlite_sequence'")->fetchColumn()) {
            $pdo->exec('DELETE FROM sqlite_sequence');
        }
        $pdo->exec('PRAGMA foreign_keys = ON');
    }

    private function createDirectory(string $directory): void
    {
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Impossible de créer le dossier writable.');
        }
    }
}
