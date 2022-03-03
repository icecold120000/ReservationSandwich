<?php

namespace App\Repository;

use App\Entity\DesactivationCommande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DesactivationCommande|null find($id, $lockMode = null, $lockVersion = null)
 * @method DesactivationCommande|null findOneBy(array $criteria, array $orderBy = null)
 * @method DesactivationCommande[]    findAll()
 * @method DesactivationCommande[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DesactivationCommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DesactivationCommande::class);
    }

    // /**
    //  * @return DesactivationCommande[] Returns an array of DesactivationCommande objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?DesactivationCommande
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
