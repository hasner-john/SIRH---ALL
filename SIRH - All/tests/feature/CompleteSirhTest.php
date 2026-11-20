<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\DemoDatabaseSeeder;
use CodeIgniter\Test\CIUnitTestCase;

final class CompleteSirhTest extends CIUnitTestCase
{
    private const PORT = 8097;
    private const BASE_URL = 'http://127.0.0.1:8097';

    private static mixed $server = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        (new DemoDatabaseSeeder())->initialize(true);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['file', '/tmp/sirh-complete-test.log', 'a'],
            2 => ['file', '/tmp/sirh-complete-test.log', 'a'],
        ];
        self::$server = proc_open(
            [PHP_BINARY, 'spark', 'serve', '--host', '127.0.0.1', '--port', (string) self::PORT],
            $descriptors,
            $pipes,
            ROOTPATH
        );

        for ($attempt = 0; $attempt < 30; $attempt++) {
            $response = @file_get_contents(self::BASE_URL . '/connexion');
            if ($response !== false) {
                return;
            }
            usleep(200000);
        }

        self::fail('Le serveur HTTP de test n’a pas démarré.');
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
        }
        (new DemoDatabaseSeeder())->initialize(true);
        parent::tearDownAfterClass();
    }

    public function testAuthenticationAndRoleHomes(): void
    {
        $loginCookie = tempnam(sys_get_temp_dir(), 'sirh-login-');
        $login = $this->request('POST', '/connexion', [
            'email' => 'direction@test.local',
            'mot_de_passe' => 'password',
        ], $loginCookie);
        $this->assertContains($login['status'], [302, 303]);

        $roles = [
            'DIRECTION' => ['/direction', '/direction/offres', '/direction/candidatures'],
            'RH' => ['/rh', '/rh/demandes', '/rh/offres', '/rh/employes', '/rh/temps-travail', '/rh/conges', '/rh/modules-avances', '/rh/entretiens', '/rh/tests', '/rh/evaluations', '/rh/requetes'],
            'DEPARTEMENT' => ['/departement', '/departement/postes', '/departement/postes/nouveau', '/departement/demandes', '/departement/demandes/nouveau'],
            'CANDIDAT' => ['/candidat', '/candidat/offres', '/candidat/mes-candidatures', '/candidat/notifications', '/candidat/requetes', '/candidat/contrats'],
            'ADMINISTRATEUR' => ['/admin', '/rh/demandes', '/rh/modules-avances'],
            'FINANCE' => ['/direction'],
        ];

        foreach ($roles as $role => $pages) {
            $cookie = tempnam(sys_get_temp_dir(), 'sirh-' . strtolower($role) . '-');
            $loginResponse = $this->request('GET', '/dev/login-as/' . $role, [], $cookie);
            $this->assertContains($loginResponse['status'], [302, 303]);

            foreach ($pages as $page) {
                $response = $this->request('GET', $page, [], $cookie);
                $this->assertContains(
                    $response['status'],
                    [200],
                    "Page $page devrait répondre 200 pour le rôle $role."
                );
            }

            $home = $this->request('GET', $pages[0], [], $cookie);
            $this->assertStringContainsString(
                'Déconnexion',
                $home['body'],
                "La page d’accueil $role doit afficher un bouton ou lien de déconnexion."
            );
            $logout = $this->request('GET', '/deconnexion', [], $cookie);
            $this->assertContains($logout['status'], [302, 303]);
        }

        @unlink($loginCookie);
    }

    public function testBlocsOneToSevenNavigation(): void
    {
        $cookie = tempnam(sys_get_temp_dir(), 'sirh-rh-blocs1-7-');
        $this->request('GET', '/dev/login-as/RH', [], $cookie);

        $pages = [
            '/rh/demandes',
            '/rh/offres',
            '/rh/offres/demandes',
            '/rh/entretiens',
            '/rh/tests',
            '/rh/evaluations',
            '/rh/requetes',
            '/rh/employes',
            '/rh/temps-travail',
            '/rh/conges',
        ];

        foreach ($pages as $page) {
            $response = $this->request('GET', $page, [], $cookie);
            $this->assertSame(200, $response['status'], "Navigation bloquée sur $page.");
        }
    }

    public function testBlocsEightToTwelveActions(): void
    {
        $cookie = tempnam(sys_get_temp_dir(), 'sirh-rh-blocs8-12-');
        $this->request('GET', '/dev/login-as/RH', [], $cookie);

        $payroll = $this->request('POST', '/rh/modules-avances/paie/calculer', ['id_periode' => 2], $cookie);
        $this->assertContains($payroll['status'], [302, 303]);

        $career = $this->request('POST', '/rh/modules-avances/carriere', [
            'id_employe' => 1,
            'type_mouvement' => 'PROMOTION',
            'nouveau_poste' => 'Lead développeur',
            'date_effet' => '2026-12-01',
            'motif' => 'Test PHPUnit',
        ], $cookie);
        $this->assertContains($career['status'], [302, 303]);

        $formation = $this->request('POST', '/rh/modules-avances/formations', [
            'titre' => 'Formation PHPUnit',
            'description' => 'Test automatique',
            'date_debut' => '2026-12-10',
            'date_fin' => '2026-12-11',
            'prestataire' => 'Test',
            'cout' => 100000,
        ], $cookie);
        $this->assertContains($formation['status'], [302, 303]);

        $incident = $this->request('POST', '/rh/modules-avances/incidents', [
            'id_employe' => 4,
            'date_incident' => '2026-11-19',
            'objet' => 'Test PHPUnit incident',
            'description' => 'Test automatique',
        ], $cookie);
        $this->assertContains($incident['status'], [302, 303]);

        $rupture = $this->request('POST', '/rh/modules-avances/ruptures', [
            'id_employe' => 4,
            'motif' => 'DEMISSION',
            'date_notification' => '2026-12-01',
            'date_sortie' => '2027-01-01',
            'motif_detail' => 'Test PHPUnit',
        ], $cookie);
        $this->assertContains($rupture['status'], [302, 303]);

        $db = new \PDO('sqlite:' . WRITEPATH . 'database.db');
        $this->assertGreaterThan(0, (int) $db->query('SELECT COUNT(*) FROM bulletin_paie')->fetchColumn());
        $this->assertGreaterThan(0, (int) $db->query('SELECT COUNT(*) FROM mouvement_carriere')->fetchColumn());
        $this->assertGreaterThan(0, (int) $db->query('SELECT COUNT(*) FROM formation')->fetchColumn());
        $this->assertGreaterThan(0, (int) $db->query('SELECT COUNT(*) FROM incident')->fetchColumn());
        $this->assertGreaterThan(0, (int) $db->query('SELECT COUNT(*) FROM rupture_contrat')->fetchColumn());
    }

    public function testAdministratorCanValidateRecruitmentRequest(): void
    {
        $cookie = tempnam(sys_get_temp_dir(), 'sirh-admin-validation-');
        $this->request('GET', '/dev/login-as/ADMINISTRATEUR', [], $cookie);

        $db = new \PDO('sqlite:' . WRITEPATH . 'database.db');
        $db->exec("UPDATE demande_recrutement SET statut='EN_ATTENTE' WHERE id_demande=1");

        $decision = $this->request('POST', '/rh/demandes/decision', [
            'id_demande' => 1,
            'nouveau_statut' => 'VALIDE',
            'commentaire' => 'Validation automatique PHPUnit',
        ], $cookie);
        $this->assertContains($decision['status'], [302, 303]);

        $db = new \PDO('sqlite:' . WRITEPATH . 'database.db');
        $statut = $db->query('SELECT statut FROM demande_recrutement WHERE id_demande=1')->fetchColumn();
        $this->assertSame('VALIDE', $statut);

        $validation = $db->query(
            'SELECT type_validation FROM validation_demande WHERE id_demande=1 ORDER BY id_validation DESC LIMIT 1'
        )->fetchColumn();
        $this->assertSame('ADMINISTRATEUR', $validation);
    }

    private function request(string $method, string $path, array $data = [], ?string $cookieJar = null): array
    {
        $ch = curl_init(self::BASE_URL . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        if ($cookieJar !== null) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
        }
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['status' => $status, 'body' => (string) $body];
    }
}
