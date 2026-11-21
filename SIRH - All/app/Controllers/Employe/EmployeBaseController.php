<?php

declare(strict_types=1);

namespace App\Controllers\Employe;

use App\Controllers\BaseController;

/** Base de l'espace employé : retrouve l'employé connecté (utilisateur → personne → employé). */
abstract class EmployeBaseController extends BaseController
{
    protected \CodeIgniter\Database\BaseConnection $db;
    protected ?array $emp = null;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();
        helper(['form', 'url']);
        $this->emp = $this->db->table('employe e')->select('e.*, pe.nom, pe.prenom')
            ->join('personne pe', 'pe.id_personne = e.id_personne')
            ->where('pe.id_utilisateur', (int) session()->get('id_utilisateur'))->orderBy('e.id_employe', 'DESC')->get(1)->getRowArray();
    }

    protected function uid(): int
    {
        return (int) session()->get('id_utilisateur');
    }

    protected function vue(string $vue, array $data = [])
    {
        return view($vue, $data + ['emp' => $this->emp]);
    }
}
