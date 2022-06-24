<?php

namespace App\DataFixtures;

use App\Entity\InscriptionCantine;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class InscriptionCantineFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $inscriptionCantine = new InscriptionCantine();
        $inscriptionCantine
            ->setEleve($this->getReference('eleve_1'))
            ->setArchiveInscription(false)
            ->setRepasJ1(true)
            ->setRepasJ2(true)
            ->setRepasJ3(true)
            ->setRepasJ4(true)
            ->setRepasJ5(true);
        $this->addReference('cantine_1', $inscriptionCantine);
        $manager->persist($inscriptionCantine);

        $inscriptionCantine2 = new InscriptionCantine();
        $inscriptionCantine2
            ->setEleve($this->getReference('eleve_2'))
            ->setArchiveInscription(false)
            ->setRepasJ1(false)
            ->setRepasJ2(false)
            ->setRepasJ3(true)
            ->setRepasJ4(true)
            ->setRepasJ5(false);
        $this->addReference('cantine_2', $inscriptionCantine2);
        $manager->persist($inscriptionCantine2);

        $inscriptionCantine3 = new InscriptionCantine();
        $inscriptionCantine3
            ->setEleve($this->getReference('eleve_3'))
            ->setArchiveInscription(false)
            ->setRepasJ1(true)
            ->setRepasJ2(true)
            ->setRepasJ3(true)
            ->setRepasJ4(false)
            ->setRepasJ5(true);
        $this->addReference('cantine_3', $inscriptionCantine3);
        $manager->persist($inscriptionCantine3);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            EleveFixtures::class
        ];
    }
}
