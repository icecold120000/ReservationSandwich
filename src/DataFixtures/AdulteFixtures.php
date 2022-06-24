<?php

namespace App\DataFixtures;

use App\Entity\Adulte;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class AdulteFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $adulte = new Adulte();
        $adulte
            ->setPrenomAdulte('Fethi')
            ->setNomAdulte('Ammar')
            ->setDateNaissance(new \DateTime('01-11-1961'))
            ->setCodeBarreAdulte('code_AMMAR_FETHI.png')
            ->setArchiveAdulte(false)
            ->setCompteAdulte($this->getReference('user_3'));
        $this->addReference('adulte_1', $adulte);
        $manager->persist($adulte);

        $adulte2 = new Adulte();
        $adulte2
            ->setPrenomAdulte('Stella')
            ->setNomAdulte('Ribas')
            ->setDateNaissance(new \DateTime('05-06-1981'))
            ->setCodeBarreAdulte('code_RIBAS_STELLA.png')
            ->setArchiveAdulte(false)
            ->setCompteAdulte($this->getReference('user_7'));
        $this->addReference('adulte_2', $adulte2);
        $manager->persist($adulte2);

        $adulte3 = new Adulte();
        $adulte3
            ->setPrenomAdulte('Mikael')
            ->setNomAdulte('Idasiak')
            ->setDateNaissance(new \DateTime('08-08-1980'))
            ->setCodeBarreAdulte('code_IDASIAK_MIKAEL.png')
            ->setArchiveAdulte(false)
            ->setCompteAdulte($this->getReference('user_8'));
        $this->addReference('adulte_3', $adulte3);
        $manager->persist($adulte3);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class
        ];
    }
}
