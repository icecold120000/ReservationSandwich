<?php

namespace App\Repository;

use App\Entity\Adulte;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Adulte|null find($id, $lockMode = null, $lockVersion = null)
 * @method Adulte|null findOneBy(array $criteria, array $orderBy = null)
 * @method Adulte[]    findAll()
 * @method Adulte[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AdulteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Adulte::class);
    }

    /**
     * @return Adulte[] Returns an array of Adulte objects
     */
    public function findByArchive($value): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.archiveAdulte = :val')
            ->setParameter('val', $value)
            ->orderBy('a.prenomAdulte', 'ASC')
            ->addGroupBy('a.nomAdulte')
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @return Adulte[] Returns an array of Adulte objects
     */
    public function filter($ordreNom = null, $ordrePrenom = null, $archive = null) : array
    {
        $query = $this->createQueryBuilder('a');
        if ($ordreNom != null && $ordrePrenom != null) {
            $query->addOrderBy('a.nomAdulte',$ordreNom)
                ->addOrderBy('a.prenomAdulte', $ordrePrenom);
        }
        elseif ($ordreNom == null && $ordrePrenom != null) {
            $query->orderBy('a.prenomAdulte', $ordrePrenom);
        }
        elseif ($ordrePrenom == null && $ordreNom != null) {
            $query->orderBy('a.nomAdulte', $ordreNom);
        }
        else {
            $this->findByArchive(false);
        }

        if ($archive != null) {
            $query->andWhere('a.archiveAdulte = :archive')
                ->setParameter('archive', $archive);
        }
        else {
            $this->findByArchive(false);
        }

        return $query->getQuery()->getResult();
    }


    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Adulte
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
