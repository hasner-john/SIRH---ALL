<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\PaieService;
use CodeIgniter\Test\CIUnitTestCase;

final class PaieServiceTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $db = db_connect();
        $db->query('CREATE TABLE IF NOT EXISTS db_bareme_irsa (
            id_bareme INTEGER PRIMARY KEY,
            borne_inf NUMERIC(14,2) NOT NULL,
            borne_sup NUMERIC(14,2),
            taux NUMERIC(6,4) NOT NULL,
            date_effet DATE NOT NULL,
            date_fin DATE
        )');
        $db->table('db_bareme_irsa')->truncate();
        $db->table('db_bareme_irsa')->insertBatch([
            ['borne_inf' => 0, 'borne_sup' => 350000, 'taux' => 0, 'date_effet' => '2000-01-01'],
            ['borne_inf' => 350000, 'borne_sup' => 400000, 'taux' => 0.05, 'date_effet' => '2000-01-01'],
            ['borne_inf' => 400000, 'borne_sup' => 500000, 'taux' => 0.10, 'date_effet' => '2000-01-01'],
            ['borne_inf' => 500000, 'borne_sup' => 600000, 'taux' => 0.15, 'date_effet' => '2000-01-01'],
            ['borne_inf' => 600000, 'borne_sup' => 4000000, 'taux' => 0.20, 'date_effet' => '2000-01-01'],
            ['borne_inf' => 4000000, 'borne_sup' => null, 'taux' => 0.25, 'date_effet' => '2000-01-01'],
        ]);
    }

    public function testIrsaProgressif(): void
    {
        $service = new PaieService();

        $this->assertSame(0.0, $service->calculerIrsa(350000));
        $this->assertSame(2500.0, $service->calculerIrsa(400000));
        $this->assertSame(12500.0, $service->calculerIrsa(500000));
        $this->assertSame(27500.0, $service->calculerIrsa(600000));
    }
}
