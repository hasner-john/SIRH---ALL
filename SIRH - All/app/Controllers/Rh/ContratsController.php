<?php

declare(strict_types=1);

namespace App\Controllers\Rh;

use App\Libraries\Transitions;
use App\Services\ContratPdfService;

class ContratsController extends RhController
{
    public function pdf(int $id)
    {
        try {
            [$nom, $mime, $bin] = (new ContratPdfService())->generer($id);
        } catch (\Throwable $e) {
            return $this->ko($e->getMessage());
        }
        return $this->response->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'inline; filename="' . addslashes((string) $nom) . '"')->setBody($bin);
    }

    public function changerStatut(int $id)
    {
        $vers = (string) $this->request->getPost('statut');
        $extra = $vers === 'SIGNE' ? ['date_signature' => date('Y-m-d H:i:s')] : [];
        $ancien = $this->db->table('contrat')->select('statut')->where('id_contrat', $id)->get()->getRow('statut');
        [$ok, $msg] = Transitions::appliquer('contrat', $id, $vers, (string) $this->request->getPost('motif'), $extra);
        if (! $ok) {
            return $this->ko($msg);
        }
        $this->db->table('historique_contrat')->insert([
            'id_contrat' => $id, 'ancien_statut' => $ancien, 'nouveau_statut' => $vers, 'id_utilisateur' => $this->uid(),
            'commentaire' => (string) $this->request->getPost('motif'),
        ]);
        return $this->ok('Statut du contrat mis à jour.');
    }
}
