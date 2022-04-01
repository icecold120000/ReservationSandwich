<?php

namespace App\Repository;

use App\Entity\MenuAccueil;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MenuAccueil|null find($id, $lockMode = null, $lockVersion = null)
 * @method MenuAccueil|null findOneBy(array $criteria, array $orderBy = null)
 * @method MenuAccueil[]    findAll()
 * @method MenuAccueil[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MenuAccueilRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MenuAccueil::class);
    }

    /**
     * @param $id
     * @return MenuAccueil|null
     * @throws NonUniqueResultException
     */
    public function findCurrentOne($id): ?MenuAccueil
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.id = :val')
            ->setParameter('val', $id)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    // /**
    //  * @return MenuAccueil[] Returns an array of MenuAccueil objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?MenuAccueil
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
