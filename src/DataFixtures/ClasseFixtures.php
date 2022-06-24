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
            ['libellé' => 'BTS 1ère année', 'code' => 'BTS_1'],
            ['libellé' => 'BTS 2ème année', 'code' => 'BTS_2'],
            ['libellé' => 'Terminale générale 1', 'code' => 'TLE1'],
            ['libellé' => '1ERE GENERALE 1', 'code' => '1ERE1'],
            ['libellé' => '2NDE GENERALE 1', 'code' => '2NDE1']
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
