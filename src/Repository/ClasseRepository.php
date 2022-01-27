<?php

namespace App\Repository;

use App\Entity\Classe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Classe|null find($id, $lockMode = null, $lockVersion = null)
 * @method Classe|null findOneBy(array $criteria, array $orderBy = null)
 * @method Classe[]    findAll()
 * @method Classe[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClasseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Classe::class);
    }

    /**
     * @return Classe[]
     */
    public function findAllOrderByAlphabetCroissant(): array
    {
        return $this->findBy(array(), array('libelleClasse' => 'ASC'));
    }

    /**
     * @return Classe[]
     */
    public function findAllOrderByAlphabetDecroissant(): array
    {
        return $this->findBy(array(), array('libelleClasse' => 'DESC'));
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findOneByLibelle($value): ?Classe
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.libelleClasse = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findOneByCode($value): ?Classe
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.codeClasse = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }
    // /**
    //  * @return Classe[] Returns an array of Classe objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Classe
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

}
