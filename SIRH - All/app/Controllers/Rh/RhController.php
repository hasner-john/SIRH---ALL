<?php

declare(strict_types=1);

namespace App\Controllers\Rh;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;

/** Base commune des contrôleurs RH des blocs 8 à 12 : connexion DB, retours flash, listes usuelles. */
abstract class RhController extends BaseController
{
    protected \CodeIgniter\Database\BaseConnection $db;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();
        helper(['form', 'url']);
    }

    protected function uid(): int
    {
        return (int) session()->get('id_utilisateur');
    }

    protected function ok(string $msg, ?string $to = null): RedirectResponse
    {
        $r = $to ? redirect()->to(site_url($to)) : redirect()->back();
        return $r->with('success', $msg);
    }

    protected function ko(string $msg, ?string $to = null): RedirectResponse
    {
        $r = $to ? redirect()->to(site_url($to)) : redirect()->back();
        return $r->withInput()->with('error', $msg);
    }

    /** Employés (ACTIF par défaut) avec identité. */
    protected function employes(array $statuts = ['ACTIF']): array
    {
        return $this->db->table('employe e')->select('e.id_employe, e.matricule, e.statut, pe.nom, pe.prenom')
            ->join('personne pe', 'pe.id_personne = e.id_personne')->whereIn('e.statut', $statuts)
            ->orderBy('pe.nom')->get()->getResultArray();
    }

    protected function validDate(?string $d): bool
    {
        $p = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $d);
        return $p !== false && $p->format('Y-m-d') === $d;
    }
}
