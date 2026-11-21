<?php

declare(strict_types=1);

namespace App\Controllers\Rh;

use App\Libraries\Audit;
use App\Services\ContratPdfService;
use App\Services\NotificationService;
use App\Services\PeriodePaieService;

/** Embauche directe (sans candidature) : RH et Direction (même contrôleur, deux entrées de route). */
class EmbaucheController extends RhController
{
    private function layout(): string
    {
        return in_array(session()->get('role'), ['DIRECTION', 'FINANCE'], true) ? 'direction/_layout' : 'rh/layout';
    }

    private function prefixe(): string
    {
        return $this->layout() === 'direction/_layout' ? 'direction' : 'rh';
    }

    public function nouveau()
    {
        $personnes = $this->db->query(
            "SELECT pe.id_personne, pe.nom, pe.prenom, u.email FROM personne pe JOIN utilisateur u ON u.id_utilisateur = pe.id_utilisateur
             WHERE pe.id_personne NOT IN (SELECT id_personne FROM employe WHERE statut != 'PARTI') AND u.role != 'ADMINISTRATEUR'
             ORDER BY pe.nom"
        )->getResultArray();
        $contrats = $this->db->query(
            "SELECT c.id_contrat, c.reference, c.type_contrat, c.salaire FROM contrat c
             WHERE c.statut = 'SIGNE' AND c.id_contrat NOT IN (SELECT id_contrat FROM employe) ORDER BY c.reference"
        )->getResultArray();
        return view('rh/embauche/formulaire', [
            'titre' => 'Embauche directe', 'layout' => $this->layout(), 'prefixe' => $this->prefixe(),
            'personnes' => $personnes, 'contrats' => $contrats,
            'postes' => $this->db->table('poste')->where('statut', 'ACTIF')->orderBy('libelle')->get()->getResultArray(),
            'departements' => $this->db->table('departement')->where('actif', 1)->orderBy('nom')->get()->getResultArray(),
            'regimes' => $this->db->table('regime_travail')->where('actif', 1)->get()->getResultArray(),
        ]);
    }

    public function creer()
    {
        $p = $this->request->getPost();
        $db = $this->db;
        $retour = $this->prefixe() . '/employes/nouveau';
        $modePers = (string) ($p['mode_personne'] ?? 'EXISTANTE');
        $modeContrat = (string) ($p['mode_contrat'] ?? 'EXISTANT');

        $db->transBegin();
        try {
            // 1. Personne (+ compte EMPLOYE si nouvelle)
            if ($modePers === 'NOUVELLE') {
                $email = strtolower(trim((string) ($p['email'] ?? '')));
                if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen((string) ($p['mot_de_passe'] ?? '')) < 8
                    || trim((string) ($p['nom'] ?? '')) === '' || trim((string) ($p['prenom'] ?? '')) === '') {
                    throw new \RuntimeException('Email valide, mot de passe (8 caractères min), nom et prénom requis.');
                }
                if ($db->table('utilisateur')->where('email', $email)->countAllResults() > 0) {
                    throw new \RuntimeException('Cet email est déjà utilisé.');
                }
                $db->table('utilisateur')->insert(['email' => $email, 'mot_de_passe_hash' => password_hash((string) $p['mot_de_passe'], PASSWORD_DEFAULT), 'role' => 'EMPLOYE', 'actif' => 1]);
                $idUser = (int) $db->insertID();
                $db->table('personne')->insert([
                    'id_utilisateur' => $idUser, 'nom' => trim($p['nom']), 'prenom' => trim($p['prenom']),
                    'telephone' => trim((string) ($p['telephone'] ?? '')) ?: null, 'adresse' => trim((string) ($p['adresse'] ?? '')) ?: null,
                    'date_naissance' => ($p['date_naissance'] ?? '') ?: null,
                ]);
                $idPersonne = (int) $db->insertID();
            } else {
                $idPersonne = (int) ($p['id_personne'] ?? 0);
                $pe = $db->table('personne')->where('id_personne', $idPersonne)->get()->getRowArray();
                if (! $pe) {
                    throw new \RuntimeException('Personne introuvable.');
                }
                if ($db->table('employe')->where('id_personne', $idPersonne)->whereIn('statut', ['ACTIF', 'SUSPENDU'])->countAllResults() > 0) {
                    throw new \RuntimeException('Cette personne a déjà un employé actif.');
                }
                $idUser = (int) $pe['id_utilisateur'];
                $db->table('utilisateur')->where('id_utilisateur', $idUser)->whereIn('role', ['CANDIDAT'])->update(['role' => 'EMPLOYE']);
            }

            // 2. Contrat
            $idPoste = (int) ($p['id_poste'] ?? 0) ?: null;
            if ($modeContrat === 'NOUVEAU') {
                $type = (string) ($p['type_contrat'] ?? '');
                $debut = (string) ($p['date_debut'] ?? '');
                $fin = ($p['date_fin'] ?? '') ?: null;
                if (! in_array($type, ['CE', 'CDD', 'CDI'], true) || ! $this->validDate($debut) || (float) ($p['salaire'] ?? -1) < 0 || ! $idPoste) {
                    throw new \RuntimeException('Type, date de début, salaire et poste du contrat requis.');
                }
                if ($type !== 'CDI' && ! $fin) {
                    throw new \RuntimeException('La date de fin est obligatoire pour un CDD ou un CE.');
                }
                if ($fin && $fin < $debut) {
                    throw new \RuntimeException('La date de fin précède la date de début.');
                }
                $db->table('contrat')->insert([
                    'id_candidature' => null, 'id_poste' => $idPoste, 'origine' => 'DIRECT', 'reference' => $this->referenceContrat(),
                    'type_contrat' => $type, 'date_debut' => $debut, 'date_fin' => $type === 'CDI' ? null : $fin, 'salaire' => (float) $p['salaire'],
                    'periodicite_salaire' => trim((string) ($p['periodicite_salaire'] ?? 'MENSUEL')) ?: 'MENSUEL', 'lieu_travail' => $p['lieu_travail'] ?? null,
                    'missions' => $p['missions'] ?? null, 'horaires' => $p['horaires'] ?? null, 'conges_absences' => $p['conges_absences'] ?? null,
                    'statut' => 'SIGNE', 'date_signature' => date('Y-m-d H:i:s'),
                ]);
                $idContrat = (int) $db->insertID();
                $db->table('historique_contrat')->insert(['id_contrat' => $idContrat, 'ancien_statut' => null, 'nouveau_statut' => 'SIGNE', 'id_utilisateur' => $this->uid(), 'commentaire' => 'Embauche directe']);
            } else {
                $idContrat = (int) ($p['id_contrat'] ?? 0);
                $c = $db->table('contrat')->where('id_contrat', $idContrat)->get()->getRowArray();
                if (! $c || $c['statut'] !== 'SIGNE' || $db->table('employe')->where('id_contrat', $idContrat)->countAllResults() > 0) {
                    throw new \RuntimeException('Contrat introuvable, non signé ou déjà utilisé.');
                }
                $idPoste = $idPoste ?: ($c['id_poste'] ? (int) $c['id_poste'] : null);
            }
            $contrat = $db->table('contrat')->where('id_contrat', $idContrat)->get()->getRowArray();

            // 3. Employé
            $dateEmb = ($p['date_embauche'] ?? '') ?: $contrat['date_debut'];
            $idDep = (int) ($p['id_departement'] ?? 0);
            if (! $this->validDate($dateEmb) || $idDep <= 0 || trim((string) ($p['fonction'] ?? '')) === '') {
                throw new \RuntimeException('Date d’embauche, département et fonction requis.');
            }
            $matricule = 'EMP-' . sprintf('%03d', ((int) $db->table('employe')->selectMax('id_employe')->get()->getRow('id_employe')) + 1);
            $db->table('employe')->insert([
                'id_contrat' => $idContrat, 'id_personne' => $idPersonne, 'matricule' => $matricule, 'date_embauche' => $dateEmb, 'statut' => 'ACTIF',
                'numero_cnaps' => trim((string) ($p['numero_cnaps'] ?? '')) ?: null, 'categorie' => trim((string) ($p['categorie'] ?? '')) ?: null,
                'secteur' => in_array($p['secteur'] ?? '', ['AGRICOLE', 'NON_AGRICOLE'], true) ? $p['secteur'] : 'NON_AGRICOLE',
                'id_poste' => $idPoste, 'id_regime' => (int) ($p['id_regime'] ?? 0) ?: null,
            ]);
            $idEmp = (int) $db->insertID();
            if ($idContrat && ! $contrat['id_poste'] && $idPoste) {
                $db->table('contrat')->where('id_contrat', $idContrat)->update(['id_poste' => $idPoste]);
            }
            // 4-7
            $db->table('affectation')->insert(['id_employe' => $idEmp, 'id_departement' => $idDep, 'date_debut' => $dateEmb, 'fonction' => trim($p['fonction'])]);
            $db->table('historique_employe')->insert(['id_employe' => $idEmp, 'ancien_statut' => null, 'nouveau_statut' => 'ACTIF', 'id_utilisateur' => $this->uid(), 'commentaire' => 'Embauche directe']);
            $db->table('historique_salaire')->insert([
                'id_employe' => $idEmp, 'ancien_salaire' => null, 'nouveau_salaire' => $contrat['salaire'], 'periodicite_salaire' => $contrat['periodicite_salaire'],
                'motif' => 'Embauche directe', 'id_utilisateur' => $this->uid(), 'date_effet' => $dateEmb,
            ]);
            $idAnnuel = $db->table('type_conge')->where('code', 'ANNUEL')->get()->getRow('id_type_conge');
            if ($idAnnuel) {
                $db->query('INSERT OR IGNORE INTO solde_conge (id_employe, id_type_conge, annee, acquis, pris, ajuste) VALUES (?,?,?,0,0,0)', [$idEmp, $idAnnuel, (int) date('Y')]);
            }
            // 8. Période de paie du mois d'embauche
            $per = PeriodePaieService::assurer((int) date('n', strtotime($dateEmb)), (int) date('Y', strtotime($dateEmb)));
            // 9. Audit + notification
            Audit::log('CREATE', 'employe', $idEmp, null, ['matricule' => $matricule, 'contrat' => $idContrat], 'Embauche directe');
            NotificationService::versEmploye($idEmp, 'Bienvenue', 'Votre dossier employé a été créé (matricule ' . $matricule . ').', '/employe');
            // PDF du contrat (un contrat signé est généré une seule fois)
            (new ContratPdfService())->generer($idContrat);

            if (! $db->transStatus()) {
                throw new \RuntimeException('Erreur base de données.');
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->ko($e->getMessage(), $retour);
        }

        $msg = "Employé $matricule embauché.";
        if ($per['verrouillee']) {
            $msg .= ' Attention : la période de paie du mois d’embauche est verrouillée, le salaire du mois sera régularisé par une variable RAPPEL le mois suivant.';
        }
        return $this->ok($msg, $this->prefixe() === 'rh' ? 'rh/employes/' . $idEmp : 'direction');
    }

    private function referenceContrat(): string
    {
        $an = date('Y');
        $max = 0;
        foreach ($this->db->table('contrat')->select('reference')->like('reference', "CTR-$an-", 'after')->get()->getResultArray() as $r) {
            $max = max($max, (int) substr($r['reference'], strlen("CTR-$an-")));
        }
        return sprintf('CTR-%s-%03d', $an, $max + 1);
    }
}
