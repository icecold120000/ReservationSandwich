<?php

namespace App\DataFixtures;

use App\Entity\LieuLivraison;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LieuLivraisonFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $lieuLivraison = new LieuLivraison();
        $lieuLivraison
            ->setLibelleLieu('Aucun')
            ->setEstActive(false);
        $this->addReference('lieu_1', $lieuLivraison);
        $manager->persist($lieuLivraison);

        $lieuLivraison2 = new LieuLivraison();
        $lieuLivraison2
            ->setLibelleLieu('Accueil')
            ->setEstActive(true);
        $this->addReference('lieu_2', $lieuLivraison2);
        $manager->persist($lieuLivraison2);

        $lieuLivraison3 = new LieuLivraison();
        $lieuLivraison3
            ->setLibelleLieu('Self')
            ->setEstActive(true);
        $this->addReference('lieu_3', $lieuLivraison3);
        $manager->persist($lieuLivraison3);

        $manager->flush();
    }
}
