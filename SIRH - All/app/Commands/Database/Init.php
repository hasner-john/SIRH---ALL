<?php

declare(strict_types=1);

namespace App\Commands\Database;

use App\Services\DemoDatabaseSeeder;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class Init extends BaseCommand
{
    protected $group = 'Database';
    protected $name = 'db:init';
    protected $description = 'Crée le schéma SQLite et charge les données de démonstration.';

    public function run(array $params)
    {
        try {
            (new DemoDatabaseSeeder())->initialize();
            CLI::write('Base initialisée avec les données de démonstration.', 'green');
        } catch (\Throwable $exception) {
            CLI::error($exception->getMessage());
            return EXIT_DATABASE;
        }
        return EXIT_SUCCESS;
    }
}
