<?php

namespace App\Repository;

use App\Entity\LieuLivraison;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LieuLivraison|null find($id, $lockMode = null, $lockVersion = null)
 * @method LieuLivraison|null findOneBy(array $criteria, array $orderBy = null)
 * @method LieuLivraison[]    findAll()
 * @method LieuLivraison[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LieuLivraisonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LieuLivraison::class);
    }

    /**
     * @return LieuLivraison[] Returns an array of Boisson objects
     */
    public function filter(bool $active = null, string $order = 'ASC'): array
    {
        $query = $this->createQueryBuilder('l');
        if ($active !== null) {
            $query->andWhere('l.estActive = :val')
                ->setParameter('val', $active);
        }
        return $query->orderBy('l.libelleLieu', $order)->getQuery()->getResult();
    }

    // /**
    //  * @return LieuLivraison[] Returns an array of LieuLivraison objects
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
    public function findOneBySomeField($value): ?LieuLivraison
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
