<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Filtre d'autorisation par rôle.
 *
 * Usage dans les routes :
 *   $routes->group('rh', ['filter' => 'role:RH'], ...)
 *   $routes->group('admin', ['filter' => 'role:ADMINISTRATEUR,RH'], ...)
 *
 * Prérequis session (à poser à la connexion) :
 *   - id_utilisateur
 *   - email
 *   - role   (valeur exacte du CHECK SQL : DEPARTEMENT, RH, DIRECTION, CANDIDAT, ADMINISTRATEUR, FINANCE)
 *   - nom, prenom (optionnel, confort UI)
 */
class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if (! $session->get('id_utilisateur') || ! $session->get('role')) {
            return redirect()
                ->to('/connexion')
                ->with('error', 'Veuillez vous connecter.');
        }

        // Coupure des accès : un compte désactivé (ex. sortie de l'employé) perd sa session
        $actif = \Config\Database::connect()->table('utilisateur')->select('actif')
            ->where('id_utilisateur', (int) $session->get('id_utilisateur'))->get()->getRow('actif');
        if ($actif === null || (int) $actif === 0) {
            $session->destroy();
            return redirect()->to('/connexion')->with('error', 'Votre compte est désactivé.');
        }

        // L'administrateur passe partout
        if (strtoupper((string) $session->get('role')) === 'ADMINISTRATEUR') {
            return;
        }

        // Aucun argument = simplement "utilisateur connecté"
        if ($arguments === null || $arguments === []) {
            return;
        }

        $userRole = strtoupper((string) $session->get('role'));
        $allowed  = array_map('strtoupper', $arguments);

        if (! in_array($userRole, $allowed, true)) {
            // Redirection vers l'accueil du rôle de l'utilisateur, pas vers login
            $home = $this->homeForRole($userRole);

            return redirect()
                ->to($home)
                ->with('error', 'Accès non autorisé pour votre rôle.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // rien
    }

    private function homeForRole(string $role): string
    {
        return match ($role) {
            'DEPARTEMENT'    => '/departement',
            'RH'             => '/rh',
            'DIRECTION'      => '/direction',
            'CANDIDAT'       => '/candidat',
            'ADMINISTRATEUR' => '/admin',
            'EMPLOYE'        => '/employe',
            'FINANCE'        => '/direction', // ou /finance si espace dédié plus tard
            default          => '/connexion',
        };
    }
}
