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
    public function filterClasse($order, $search = null): array
    {
        $query = $this->createQueryBuilder('c');
        if ($search != null) {
            $query
                ->andWhere('c.codeClasse Like :search or c.libelleClasse Like :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $query->orderBy('c.codeClasse', $order);
        return $query->getQuery()->getResult();
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
            ->getOneOrNullResult();
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
            ->getOneOrNullResult();
    }
}
