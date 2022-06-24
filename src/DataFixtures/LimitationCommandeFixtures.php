<?php

namespace App\DataFixtures;

use App\Entity\LimitationCommande;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LimitationCommandeFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $limitationCommande = new LimitationCommande();
        $limitationCommande
            ->setLibelleLimite('Heure de clôture des commandes')
            ->setIsActive(true)
            ->setNbLimite(null)
            ->setHeureLimite(new \DateTime('09:30:00'));
        $manager->persist($limitationCommande);

        $limitationCommande2 = new LimitationCommande();
        $limitationCommande2
            ->setLibelleLimite('Nombre de commandes journalières')
            ->setIsActive(false)
            ->setNbLimite(3)
            ->setHeureLimite(null);
        $manager->persist($limitationCommande2);

        $limitationCommande3 = new LimitationCommande();
        $limitationCommande3
            ->setLibelleLimite('Nombre de commande hebdomadaires')
            ->setIsActive(false)
            ->setNbLimite(21)
            ->setHeureLimite(null);
        $manager->persist($limitationCommande3);

        $limitationCommande4 = new LimitationCommande();
        $limitationCommande4
            ->setLibelleLimite('Nombre de commande mensuelles')
            ->setIsActive(false)
            ->setNbLimite(120)
            ->setHeureLimite(null);
        $manager->persist($limitationCommande4);

        $limitationCommande5 = new LimitationCommande();
        $limitationCommande5
            ->setLibelleLimite('Nombre de jour minimum avant sortie')
            ->setIsActive(true)
            ->setNbLimite(7)
            ->setHeureLimite(null);
        $manager->persist($limitationCommande5);

        $limitationCommande6 = new LimitationCommande();
        $limitationCommande6
            ->setLibelleLimite('Heure de début du service')
            ->setIsActive(true)
            ->setNbLimite(null)
            ->setHeureLimite(new \DateTime('11:30:00'));
        $manager->persist($limitationCommande6);

        $limitationCommande7 = new LimitationCommande();
        $limitationCommande7
            ->setLibelleLimite('Heure de fin du service')
            ->setIsActive(true)
            ->setNbLimite(null)
            ->setHeureLimite(new \DateTime('13:20:00'));
        $manager->persist($limitationCommande7);

        $manager->flush();
    }
}
