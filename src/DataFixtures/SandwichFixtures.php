<?php

namespace App\DataFixtures;

use App\Entity\Sandwich;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SandwichFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $sandwich = new Sandwich();
        $sandwich
            ->setNomSandwich('Sandwich Jambon')
            ->setIngredientSandwich('Pain, Jambon,Beurre , Salade')
            ->setCommentaireSandwich('Déconseillé pour les végétariens et véganes.')
            ->setDispoSandwich(true)
            ->setImageSandwich('jambon-beurre.jpg');
        $this->addReference('sandwich_1', $sandwich);
        $manager->persist($sandwich);

        $sandwich2 = new Sandwich();
        $sandwich2
            ->setNomSandwich('Sandwich Poulet')
            ->setIngredientSandwich('Pain, Poulet, Salade')
            ->setCommentaireSandwich('Déconseillé pour les véganes.')
            ->setDispoSandwich(true)
            ->setImageSandwich('sandwichPoulet.jpg');
        $this->addReference('sandwich_2', $sandwich2);
        $manager->persist($sandwich2);

        $sandwich3 = new Sandwich();
        $sandwich3
            ->setNomSandwich('Hot dog')
            ->setIngredientSandwich('Pain, Saucisse, Fromage')
            ->setCommentaireSandwich('Déconseillé pour les végétariens et véganes.')
            ->setDispoSandwich(true)
            ->setImageSandwich('Hotdog.jpg');
        $this->addReference('sandwich_3', $sandwich3);
        $manager->persist($sandwich3);

        $manager->flush();
    }
}
