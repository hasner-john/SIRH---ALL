<?php

namespace App\Controllers;

use App\Models\PersonneModel;
use App\Models\UtilisateurModel;

/**
 * Authentification et inscription des candidats.
 */
class AuthController extends BaseController
{
    public function connexion()
    {
        // Si déjà connecté → rediriger vers l'espace du rôle
        if (session()->get('id_utilisateur') && session()->get('role')) {
            helper('role');
            return redirect()->to(role_home());
        }

        return view('auth/connexion');
    }

    public function checkConnexion()
    {
        $email = UtilisateurModel::normaliserEmail((string) $this->request->getPost('email'));
        $password = (string) $this->request->getPost('mot_de_passe');

        if ($email === '' || $password === '') {
            return redirect()->back()->withInput()->with('error', 'Email ou mot de passe incorrect.');
        }

        $user = (new UtilisateurModel())->findByEmail($email);
        if (! $user || ! (bool) $user->actif || ! password_verify($password, $user->mot_de_passe_hash)) {
            return redirect()->back()->withInput()->with('error', 'Email ou mot de passe incorrect.');
        }

        $personne = (new PersonneModel())->findByUtilisateur((int) $user->id_utilisateur);
        session()->regenerate(true);
        session()->set([
            'id_utilisateur' => (int) $user->id_utilisateur,
            'email' => $user->email,
            'role' => $user->role,
            'nom' => $personne?->nom,
            'prenom' => $personne?->prenom,
        ]);

        helper('role');
        return redirect()->to(role_home($user->role));
    }

    public function inscription()
    {
        return view('auth/inscription');
    }

    public function registerCandidat()
    {
        $data = $this->request->getPost();
        $email = UtilisateurModel::normaliserEmail((string) ($data['email'] ?? ''));
        $password = (string) ($data['mot_de_passe'] ?? '');
        $nom = trim((string) ($data['nom'] ?? ''));
        $prenom = trim((string) ($data['prenom'] ?? ''));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)
            || strlen($password) < 8
            || $nom === ''
            || $prenom === '') {
            return redirect()->back()->withInput()->with(
                'error',
                'Veuillez fournir un email valide, un mot de passe de 8 caractères minimum, un nom et un prénom.'
            );
        }

        $db = \Config\Database::connect();
        if ((new UtilisateurModel())->emailExiste($email)) {
            return redirect()->back()->withInput()->with('error', 'Cette adresse email est déjà utilisée.');
        }

        $db->transBegin();
        try {
            (new UtilisateurModel())->insert([
                'email' => $email,
                'mot_de_passe_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'CANDIDAT',
                'actif' => 1,
            ]);
            $idUtilisateur = (int) $db->insertID();
            if ($idUtilisateur <= 0) {
                throw new \RuntimeException('Compte non créé.');
            }

            (new PersonneModel())->insert([
                'id_utilisateur' => $idUtilisateur,
                'nom' => $nom,
                'prenom' => $prenom,
                'telephone' => trim((string) ($data['telephone'] ?? '')) ?: null,
            ]);
            $idPersonne = (int) $db->insertID();
            if ($idPersonne <= 0) {
                throw new \RuntimeException('Profil non créé.');
            }

            $db->table('candidat')->insert(['id_personne' => $idPersonne]);
            if (! $db->transStatus()) {
                throw new \RuntimeException('Inscription impossible.');
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'AuthController::registerCandidat: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Inscription impossible.');
        }

        return redirect()->to('/connexion')->with('success', 'Compte candidat créé. Vous pouvez vous connecter.');
    }

    public function deconnexion()
    {
        session()->destroy();
        return redirect()->to('/connexion')->with('success', 'Vous êtes déconnecté.');
    }
}
