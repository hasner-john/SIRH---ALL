<?php
    namespace App\Models;

    use CodeIgniter\Model;

    class CandidatModel extends Model
    {
        protected $table = 'candidat';
        protected $primaryKey ='id_candidat';
        protected $returnType ='object';
        protected $useAutoIcrement =true;
        protected $useTimestamps = false;

        protected $allowedFields =[
            'id_personne',
            'niveau_etude',
            'experience',
        ];
    }
?>

