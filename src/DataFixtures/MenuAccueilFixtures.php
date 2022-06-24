<?php

namespace App\DataFixtures;

use App\Entity\MenuAccueil;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class MenuAccueilFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $menuAccueil = new MenuAccueil();
        $menuAccueil->setFileName('Capture-d-ecran-2022-03-14-142329.png');
        $manager->persist($menuAccueil);

        $manager->flush();
    }
}
