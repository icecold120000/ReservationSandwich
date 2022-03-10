<?php

namespace App\Repository;

use App\Entity\SandwichCommandeGroupe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SandwichCommandeGroupe|null find($id, $lockMode = null, $lockVersion = null)
 * @method SandwichCommandeGroupe|null findOneBy(array $criteria, array $orderBy = null)
 * @method SandwichCommandeGroupe[]    findAll()
 * @method SandwichCommandeGroupe[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SandwichCommandeGroupeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SandwichCommandeGroupe::class);
    }

    // /**
    //  * @return SandwichCommandeGroupe[] Returns an array of SandwichCommandeGroupe objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SandwichCommandeGroupe
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
