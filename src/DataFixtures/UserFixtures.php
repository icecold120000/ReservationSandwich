<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {

        $user = new User();
        $password = $this->hasher->hashPassword($user, 'admin');
        $user
            ->setNomUser('Admin')
            ->setPrenomUser('Admin')
            ->setDateNaissanceUser(new \DateTime('08-11-2000'))
            ->setEmail('admin@gmail.com')
            ->setRoles([User::ROLE_ADMIN])
            ->setPassword($password)
            ->setTokenHash('5nuixns798ytecHzu6JDH')
            ->setIsVerified(true);
        $this->addReference('user_1', $user);
        $manager->persist($user);

        $user2 = new User();
        $password2 = $this->hasher->hashPassword($user2, 'cuisine');
        $user2
            ->setNomUser('Stephane')
            ->setPrenomUser('Pelletier')
            ->setDateNaissanceUser(new \DateTime('20-09-1970'))
            ->setEmail('stephane.pelletier@lyceestvincent.net')
            ->setRoles([User::ROLE_CUISINE])
            ->setPassword($password2)
            ->setTokenHash('fez0dce8efz6vr7ef334byub')
            ->setIsVerified(true);
        $this->addReference('user_2', $user2);
        $manager->persist($user2);

        $user3 = new User();
        $password3 = $this->hasher->hashPassword($user3, 'adulte');
        $user3
            ->setNomUser('Ammar')
            ->setPrenomUser('Fethi')
            ->setDateNaissanceUser(new \DateTime('01-11-1961'))
            ->setEmail('fammar@nodevo.com')
            ->setRoles([User::ROLE_ADULTES])
            ->setPassword($password3)
            ->setTokenHash('ddsx79ef6r5t34FVRD56cdzd27')
            ->setIsVerified(true);
        $this->addReference('user_3', $user3);
        $manager->persist($user3);

        $user4 = new User();
        $password4 = $this->hasher->hashPassword($user4, 'eleve');
        $user4
            ->setNomUser('ACHKAR')
            ->setPrenomUser('ALEXANDRE')
            ->setDateNaissanceUser(new \DateTime('12-06-2006'))
            ->setEmail('eleve1@gmail.com')
            ->setRoles([User::ROLE_ELEVE])
            ->setPassword($password4)
            ->setTokenHash('ecdCDSsx8VK6JO4cd6b8u78uYBU')
            ->setIsVerified(true);
        $this->addReference('user_4', $user4);
        $manager->persist($user4);

        $user5 = new User();
        $password5 = $this->hasher->hashPassword($user5, 'eleve');
        $user5
            ->setNomUser('AMBOISE')
            ->setPrenomUser('PIERRE')
            ->setDateNaissanceUser(new \DateTime('12-12-2007'))
            ->setEmail('eleve2@gmail.com')
            ->setRoles([User::ROLE_ELEVE])
            ->setPassword($password5)
            ->setTokenHash('zczCczCDd5zczccd896b4u0ix6s')
            ->setIsVerified(true);
        $this->addReference('user_5', $user5);
        $manager->persist($user5);

        $user6 = new User();
        $password6 = $this->hasher->hashPassword($user6, 'eleve');
        $user6
            ->setNomUser('ANDRE')
            ->setPrenomUser('THOMAS')
            ->setDateNaissanceUser(new \DateTime('18-06-2009'))
            ->setEmail('eleve3@gmail.com')
            ->setRoles([User::ROLE_ELEVE])
            ->setPassword($password6)
            ->setTokenHash('vyGJkybfd79uu5788ub68')
            ->setIsVerified(true);
        $this->addReference('user_6', $user6);
        $manager->persist($user6);

        $user7 = new User();
        $password7 = $this->hasher->hashPassword($user7, 'adulte');
        $user7
            ->setNomUser('Ribas')
            ->setPrenomUser('Stella')
            ->setDateNaissanceUser(new \DateTime('05-06-1981'))
            ->setEmail('stelle.ribas@lyceestvincent.net')
            ->setRoles([User::ROLE_ADULTES])
            ->setPassword($password7)
            ->setTokenHash('vy4c5d678yvyuibuTYTYvuiis68')
            ->setIsVerified(true);
        $this->addReference('user_7', $user7);
        $manager->persist($user7);

        $user8 = new User();
        $password8 = $this->hasher->hashPassword($user8, 'adulte');
        $user8
            ->setNomUser('Idasiak')
            ->setPrenomUser('Mikael')
            ->setDateNaissanceUser(new \DateTime('08-08-1980'))
            ->setEmail('mikael.idasiak@lyceestvincent.net')
            ->setRoles([User::ROLE_ADULTES])
            ->setPassword($password8)
            ->setTokenHash('YV4B5IveOEev56O84C3NCvrceecZvecS')
            ->setIsVerified(true);
        $this->addReference('user_8', $user8);
        $manager->persist($user8);

        $manager->flush();
    }
}
