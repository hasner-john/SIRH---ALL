<?php
namespace Config;
use CodeIgniter\Config\BaseConfig;

class Entreprise extends BaseConfig
{
    public string $raisonSociale = 'À renseigner';
    public string $adresse = '';
    public string $stat = '';
    public string $cif = '';
}