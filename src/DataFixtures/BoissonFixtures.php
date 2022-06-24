<?php

namespace App\DataFixtures;

use App\Entity\Boisson;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class BoissonFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $boisson = new Boisson();
        $boisson
            ->setNomBoisson('Eau')
            ->setDispoBoisson(true)
            ->setImageBoisson('eau.jpg');
        $this->addReference('boisson_1', $boisson);
        $manager->persist($boisson);

        $boisson2 = new Boisson();
        $boisson2
            ->setNomBoisson('CocaCola')
            ->setDispoBoisson(true)
            ->setImageBoisson('Cocacola.jpg');
        $this->addReference('boisson_2', $boisson2);
        $manager->persist($boisson2);

        $boisson3 = new Boisson();
        $boisson3
            ->setNomBoisson('Limonade')
            ->setDispoBoisson(true)
            ->setImageBoisson('limonade.jpg');
        $this->addReference('boisson_3', $boisson3);
        $manager->persist($boisson3);

        $manager->flush();
    }
}
