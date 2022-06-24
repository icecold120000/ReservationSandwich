<?php

namespace App\DataFixtures;

use App\Entity\CommandeIndividuelle;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CommandeIndividuelleFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $commandeIndividuelle = new CommandeIndividuelle();
        $commandeIndividuelle
            ->setCommandeur($this->getReference('user_3'))
            ->setDateCreation(new \DateTime('now'))
            ->setDateHeureLivraison(new \DateTime('+7days'))
            ->setEstValide(true)
            ->setSandwichChoisi($this->getReference('sandwich_1'))
            ->setBoissonChoisie($this->getReference('boisson_3'))
            ->setDessertChoisi($this->getReference('dessert_2'))
            ->setPrendreChips(true)
            ->setRaisonCommande('Administrateur a commandé');
        $this->addReference('comInd_1', $commandeIndividuelle);
        $manager->persist($commandeIndividuelle);

        $commandeIndividuelle2 = new CommandeIndividuelle();
        $commandeIndividuelle2
            ->setCommandeur($this->getReference('user_4'))
            ->setDateCreation(new \DateTime('now'))
            ->setDateHeureLivraison(new \DateTime('+7days'))
            ->setEstValide(true)
            ->setSandwichChoisi($this->getReference('sandwich_2'))
            ->setBoissonChoisie($this->getReference('boisson_2'))
            ->setDessertChoisi($this->getReference('dessert_1'))
            ->setPrendreChips(true)
            ->setRaisonCommande('Administrateur a commandé');
        $this->addReference('comInd_2', $commandeIndividuelle2);
        $manager->persist($commandeIndividuelle2);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            SandwichFixtures::class,
            BoissonFixtures::class,
            DessertFixtures::class
        ];
    }
}
