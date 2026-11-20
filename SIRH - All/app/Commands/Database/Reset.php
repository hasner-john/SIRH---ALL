<?php

declare(strict_types=1);

namespace App\Commands\Database;

use App\Services\DemoDatabaseSeeder;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class Reset extends BaseCommand
{
    protected $group = 'Database';
    protected $name = 'db:reset';
    protected $description = 'Supprime puis recrée la base SQLite avec les données de démonstration.';
    protected $options = ['--force' => 'Ne demande pas de confirmation.'];

    public function run(array $params)
    {
        $force = array_key_exists('force', $params) || (bool) CLI::getOption('force');
        $confirmation = ! $force
            ? strtolower((string) CLI::prompt('Toutes les données seront supprimées. Continuer ? (y/N)', 'N'))
            : 'y';
        if ($confirmation !== 'y' && $confirmation !== 'yes') {
            CLI::write('Réinitialisation annulée.');
            return EXIT_USER_INPUT;
        }

        try {
            (new DemoDatabaseSeeder())->initialize(true);
            CLI::write('Base réinitialisée avec les données de démonstration.', 'green');
        } catch (\Throwable $exception) {
            CLI::error($exception->getMessage());
            return EXIT_DATABASE;
        }
        return EXIT_SUCCESS;
    }
}
