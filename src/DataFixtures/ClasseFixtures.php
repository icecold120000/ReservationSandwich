<?php

namespace App\DataFixtures;

use App\Entity\Classe;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ClasseFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $classes = [
            ['libellé' => 'Quitter l\'établissement', 'code' => 'Aucun'],
            ['libellé' => 'BTS SIO Année 1', 'code' => 'BTS_1'],
            ['libellé' => 'BTS SIO Année 2', 'code' => 'BTS_2'],
            ['libellé' => 'TERMINALE GENERALE 1', 'code' => 'TLE1'],
            ['libellé' => 'TERMINALE GENERALE 2', 'code' => 'TLE2'],
            ['libellé' => 'TERMINALE GENERALE 3', 'code' => 'TLE3'],
            ['libellé' => 'TERMINALE GENERALE 4', 'code' => 'TLE4'],
            ['libellé' => 'TERMINALE GENERALE 5', 'code' => 'TLE5'],
            ['libellé' => 'TERMINALE GENERALE 6', 'code' => 'TLE6'],
            ['libellé' => 'TERMINALE GENERALE 7', 'code' => 'TLE7'],
            ['libellé' => 'TER SC TECH SANTE SOCIAL', 'code' => 'TST2S'],
            ['libellé' => 'TER SC TECH MAN GESTION', 'code' => 'TSTMG'],
            ['libellé' => '1ERE GENERALE 1', 'code' => '1ERE1'],
            ['libellé' => '1ERE GENERALE 2', 'code' => '1ERE2'],
            ['libellé' => '1ERE GENERALE 3', 'code' => '1ERE3'],
            ['libellé' => '1ERE GENERALE 4', 'code' => '1ERE4'],
            ['libellé' => '1ERE GENERALE 5', 'code' => '1ERE5'],
            ['libellé' => '1ERE GENERALE 6', 'code' => '1ERE6'],
            ['libellé' => '1ERE GENERALE 7', 'code' => '1ERE7'],
            ['libellé' => '1ERE SCES TECH MANAG GEST', 'code' => '1STMG'],
            ['libellé' => '1-ST2S SC.&TECHNO.SANTE&S', 'code' => '1ST2S'],
            ['libellé' => '2NDE GENERALE 1', 'code' => '2NDE1'],
            ['libellé' => '2NDE GENERALE 2', 'code' => '2NDE2'],
            ['libellé' => '2NDE GENERALE 3', 'code' => '2NDE3'],
            ['libellé' => '2NDE GENERALE 4', 'code' => '2NDE4'],
            ['libellé' => '2NDE GENERALE 5', 'code' => '2NDE5'],
            ['libellé' => '2NDE GENERALE 6', 'code' => '2NDE6'],
            ['libellé' => '2NDE GENERALE 7', 'code' => '2NDE7'],
        ];

        foreach ($classes as $classeData) {
            $classe = new Classe();
            $classe
                ->setLibelleClasse($classeData['libellé'])
                ->setCodeClasse($classeData['code']);
            $this->addReference('classe_' . $classeData['code'], $classe);
            $manager->persist($classe);
        }

        $manager->flush();
    }
}
