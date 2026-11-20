<?php

namespace App\Controllers\Rh;

use App\Controllers\API\ApiController;
use App\Models\DemandeModel;
use CodeIgniter\API\ResponseTrait;


class DemandesController extends ApiController
{
    use ResponseTrait;

    public function list()
    {
        $demandeModel = new DemandeModel();
        $demandes = $demandeModel->getDemandesAvecDetails();

        return $this->respond($demandes);
    }

    public function show($id)
    {
        $demandeModel = new DemandeModel();
        $demande = $demandeModel->getDemandeAvecDetails($id);

        if (!$demande) {
            return $this->failNotFound("Demande non trouvée : $id");
        }

        return $this->respond($demande);
    }
}
