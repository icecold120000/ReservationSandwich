<?php

namespace App\DataFixtures;

use App\Entity\DesactivationCommande;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class DesactivationCommandeFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $desactivationCommande = new DesactivationCommande();
        $desactivationCommande->setIsDeactivated(false);
        $manager->persist($desactivationCommande);

        $manager->flush();
    }
}
