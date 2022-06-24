<?php

namespace App\DataFixtures;

use App\Entity\Dessert;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class DessertFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $dessert = new Dessert();
        $dessert
            ->setNomDessert('Cookies au chocolat')
            ->setIngredientDessert('Farine, Chocolat, Lait...')
            ->setCommentaireDessert('Gluten, Produit à base de lait')
            ->setDispoDessert(true)
            ->setImageDessert('cookies-aux-pepites-de-chocolat.jpg');
        $this->addReference('dessert_1', $dessert);
        $manager->persist($dessert);

        $dessert2 = new Dessert();
        $dessert2
            ->setNomDessert('Gateau au chocolat')
            ->setIngredientDessert('Farine, Chocolat, Lait...')
            ->setCommentaireDessert('Gluten, Produit à base de lait')
            ->setDispoDessert(true)
            ->setImageDessert('Gateau.jpg');
        $this->addReference('dessert_2', $dessert2);
        $manager->persist($dessert2);

        $dessert3 = new Dessert();
        $dessert3
            ->setNomDessert('Banane')
            ->setIngredientDessert(null)
            ->setCommentaireDessert('Origine d\'Espagne')
            ->setDispoDessert(true)
            ->setImageDessert('Banane.jpg');
        $this->addReference('dessert_3', $dessert3);
        $manager->persist($dessert3);

        $manager->flush();
    }
}
