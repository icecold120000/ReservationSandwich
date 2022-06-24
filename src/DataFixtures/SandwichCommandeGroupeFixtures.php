<?php

namespace App\DataFixtures;

use App\Entity\SandwichCommandeGroupe;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SandwichCommandeGroupeFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $sandwichCommandeGroupe = new SandwichCommandeGroupe();
        $sandwichCommandeGroupe
            ->setSandwichChoisi($this->getReference('sandwich_1'))
            ->setNombreSandwich(5)
            ->setCommandeAffecte($this->getReference('comGr_1'));
        $manager->persist($sandwichCommandeGroupe);

        $sandwichCommandeGroupe2 = new SandwichCommandeGroupe();
        $sandwichCommandeGroupe2
            ->setSandwichChoisi($this->getReference('sandwich_2'))
            ->setNombreSandwich(10)
            ->setCommandeAffecte($this->getReference('comGr_1'));
        $manager->persist($sandwichCommandeGroupe2);

        $sandwichCommandeGroupe3 = new SandwichCommandeGroupe();
        $sandwichCommandeGroupe3
            ->setSandwichChoisi($this->getReference('sandwich_1'))
            ->setNombreSandwich(30)
            ->setCommandeAffecte($this->getReference('comGr_2'));
        $manager->persist($sandwichCommandeGroupe3);

        $sandwichCommandeGroupe4 = new SandwichCommandeGroupe();
        $sandwichCommandeGroupe4
            ->setSandwichChoisi($this->getReference('sandwich_2'))
            ->setNombreSandwich(10)
            ->setCommandeAffecte($this->getReference('comGr_2'));
        $manager->persist($sandwichCommandeGroupe4);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            SandwichFixtures::class
        ];
    }
}
