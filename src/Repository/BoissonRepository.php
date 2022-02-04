<?php

namespace App\Repository;

use App\Entity\Boisson;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Boisson|null find($id, $lockMode = null, $lockVersion = null)
 * @method Boisson|null findOneBy(array $criteria, array $orderBy = null)
 * @method Boisson[]    findAll()
 * @method Boisson[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BoissonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Boisson::class);
    }

     /**
      * @return Boisson[] Returns an array of Boisson objects
      */
    public function filter(int $dispo = null , string $order = 'ASC'): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.dispoBoisson = :val')
            ->setParameter('val', $dispo)
            ->orderBy('b.nomBoisson', $order)
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @return Boisson[] Returns an array of Boisson objects
     */
    public function findByDispo($value): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.dispoBoisson = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getResult()
            ;
    }

    // /**
    //  * @return Boisson[] Returns an array of Boisson objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Boisson
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
