<?php

namespace App\DataFixtures;

use App\Entity\CommandeGroupe;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CommandeGroupeFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $commandeGroupe = new CommandeGroupe();
        $commandeGroupe
            ->setCommandeur($this->getReference('user_7'))
            ->setDateCreation(new \DateTime('now'))
            ->setDateHeureLivraison(new \DateTime('+14days'))
            ->setLieuLivraison($this->getReference('lieu_2'))
            ->setEstValide(true)
            ->setBoissonChoisie($this->getReference('boisson_1'))
            ->setDessertChoisi($this->getReference('dessert_1'))
            ->setPrendreChips(true)
            ->setMotifSortie('Sortie théatre')
            ->setCommentaireCommande('40 perssonnes');
        $this->addReference('comGr_1', $commandeGroupe);
        $manager->persist($commandeGroupe);

        $commandeGroupe2 = new CommandeGroupe();
        $commandeGroupe2
            ->setCommandeur($this->getReference('user_8'))
            ->setDateCreation(new \DateTime('now'))
            ->setDateHeureLivraison(new \DateTime('+14days'))
            ->setLieuLivraison($this->getReference('lieu_3'))
            ->setEstValide(true)
            ->setBoissonChoisie($this->getReference('boisson_1'))
            ->setDessertChoisi($this->getReference('dessert_3'))
            ->setPrendreChips(true)
            ->setMotifSortie('Sortie Musée de 1ère Guerre Mondiale, Verdun')
            ->setCommentaireCommande('60 personnes');
        $this->addReference('comGr_2', $commandeGroupe2);
        $manager->persist($commandeGroupe2);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            LieuLivraisonFixtures::class,
            BoissonFixtures::class,
            DessertFixtures::class
        ];
    }
}
