<?php

declare(strict_types=1);

namespace App\Controllers\Employe;

class NotificationsController extends EmployeBaseController
{
    public function index()
    {
        return $this->vue('employe/notifications', [
            'titre' => 'Mes notifications',
            'notifications' => $this->db->table('notification')->where('id_utilisateur', $this->uid())->orderBy('date_creation', 'DESC')->limit(100)->get()->getResultArray(),
        ]);
    }

    public function marquerLue(int $id)
    {
        $this->db->table('notification')->where(['id_notification' => $id, 'id_utilisateur' => $this->uid()])->update(['lue' => 1, 'date_lecture' => date('Y-m-d H:i:s')]);
        return redirect()->back();
    }

    public function toutMarquerLu()
    {
        $this->db->table('notification')->where(['id_utilisateur' => $this->uid(), 'lue' => 0])->update(['lue' => 1, 'date_lecture' => date('Y-m-d H:i:s')]);
        return redirect()->back();
    }
}
