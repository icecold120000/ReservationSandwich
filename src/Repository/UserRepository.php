<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }


    /**
     * @return User[]
     */
    public function search($roleUser = null, $userVerifie = null, $ordreNom = null, $ordrePrenom = null): array
    {
        $query = $this->createQueryBuilder('u');
        if($roleUser !== null){
            $query->andWhere('u.roles like :role')
                ->setParameter('role','%'.$roleUser.'%');
        }

        if($userVerifie !== null){
            $query->andWhere('u.isVerified = :userVerifie')
                ->setParameter('userVerifie', $userVerifie);
        }

        if ($ordreNom !== null && $ordrePrenom !== null) {
            $query->addOrderBy('u.nomUser', $ordreNom)
                ->addOrderBy('u.prenomUser', $ordrePrenom);
        }
        elseif ($ordreNom == null && $ordrePrenom !== null) {
            $query->orderBy('u.prenomUser', $ordrePrenom);
        }
        elseif ($ordrePrenom == null && $ordreNom !== null) {
            $query->orderBy('u.nomUser', $ordreNom);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findOneByEmail($email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :val')
            ->setParameter('val', $email)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findOneByEmailAndDate($email, $date): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :val')
            ->andWhere('u.dateNaissanceUser = :val2')
            ->setParameters(array('val' => $email, 'val2' => $date))
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findOneByToken($tokenHash): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.tokenHash = :val')
            ->setParameter('val', $tokenHash)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findOneByAdulte($adulteId): ?User
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.adultes','a')
            ->andWhere('a.id = :val')
            ->setParameter('val', $adulteId)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findOneByEleve($eleveId): ?User
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.eleves','e')
            ->andWhere('e.id = :val')
            ->setParameter('val', $eleveId)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    /**
     * @return User[]
     */
    public function findByNomAndPrenom($nom, $prenom): array
    {
        $query = $this->createQueryBuilder('u');
        $query->andWhere('u.nomUser Like :val')
            ->andWhere('u.prenomUser Like :val2')
            ->setParameters(array('val' =>'%'.$nom.'%', 'val2' =>'%'.$prenom.'%'));
        return $query->getQuery()->getResult();
    }

    /**
     * @return User[]
     */
    public function findByNomPrenomAndBirthday($nom, $prenom, $birthday): array
    {
        $query = $this->createQueryBuilder('u');
        $query->andWhere('u.nomUser like :val')
            ->andWhere('u.prenomUser like :val2')
            ->setParameters(array('val' =>'%'.$nom.'%', 'val2' =>'%'.$prenom.'%'));
        if($birthday != null){
            $query->andWhere('u.dateNaissance = :birthday')
                ->setParameter('birthday', $birthday);
        }
        return $query->getQuery()->getResult();
    }


    // /**
    //  * @return User[] Returns an array of User objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
