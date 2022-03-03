<?php

namespace App\Repository;

use App\Entity\LimitationCommande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LimitationCommande|null find($id, $lockMode = null, $lockVersion = null)
 * @method LimitationCommande|null findOneBy(array $criteria, array $orderBy = null)
 * @method LimitationCommande[]    findAll()
 * @method LimitationCommande[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LimitationCommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LimitationCommande::class);
    }

    // /**
    //  * @return LimitationCommande[] Returns an array of LimitationCommande objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?LimitationCommande
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
