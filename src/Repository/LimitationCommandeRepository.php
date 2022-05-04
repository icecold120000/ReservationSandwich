<?php

namespace App\Repository;

use App\Entity\LimitationCommande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
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

    /**
     * @param int $idLimite
     * @return LimitationCommande|null
     * @throws NonUniqueResultException
     */
    public function findOneById(int $idLimite): ?LimitationCommande
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.id = :limite')
            ->setParameter('limite', $idLimite)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
