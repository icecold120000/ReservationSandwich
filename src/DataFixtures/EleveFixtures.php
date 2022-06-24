<?php

namespace App\DataFixtures;

use App\Entity\Eleve;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class EleveFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $eleve = new Eleve();
        $eleve
            ->setNomEleve('ACHKAR')
            ->setPrenomEleve('ALEXANDRE')
            ->setArchiveEleve(false)
            ->setDateNaissance(new \DateTime('12-06-2006'))
            ->setClasseEleve($this->getReference('classe_BTS_1'))
            ->setNbRepas(5)
            ->setCompteEleve($this->getReference('user_4'));
        $this->addReference('eleve_1', $eleve);
        $manager->persist($eleve);

        $eleve2 = new Eleve();
        $eleve2
            ->setNomEleve('AMBOISE')
            ->setPrenomEleve('PIERRE')
            ->setArchiveEleve(false)
            ->setDateNaissance(new \DateTime('12-12-2007'))
            ->setClasseEleve($this->getReference('classe_1ERE1'))
            ->setNbRepas(2)
            ->setCompteEleve($this->getReference('user_5'));
        $this->addReference('eleve_2', $eleve2);
        $manager->persist($eleve2);

        $eleve3 = new Eleve();
        $eleve3
            ->setNomEleve('ANDRE')
            ->setPrenomEleve('THOMAS')
            ->setArchiveEleve(false)
            ->setDateNaissance(new \DateTime('18-06-2009'))
            ->setClasseEleve($this->getReference('classe_2NDE1'))
            ->setNbRepas(4)
            ->setCompteEleve($this->getReference('user_6'));
        $this->addReference('eleve_3', $eleve3);
        $manager->persist($eleve3);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            ClasseFixtures::class
        ];
    }
}
