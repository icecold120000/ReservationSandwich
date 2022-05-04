<?php

namespace App\Repository;

use App\Entity\Sandwich;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Sandwich|null find($id, $lockMode = null, $lockVersion = null)
 * @method Sandwich|null findOneBy(array $criteria, array $orderBy = null)
 * @method Sandwich[]    findAll()
 * @method Sandwich[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SandwichRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sandwich::class);
    }

    /**
     * @param int|null $dispo
     * @param string $order
     * @return Sandwich[] Returns an array of sandwich objects
     */
    public function filter(int $dispo = null, string $order = 'ASC'): array
    {
        $query = $this->createQueryBuilder('s');
        if ($dispo !== null) {
            $query->andWhere('s.dispoSandwich = :dispo')
                ->setParameter('dispo', $dispo);
        }
        return $query->orderBy('s.nomSandwich', $order)->getQuery()->getResult();
    }

    /**
     * @return Sandwich[] Returns an array of sandwich objects
     */
    public function findByDispo(bool $dispo): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.dispoSandwich = :dispo')
            ->setParameter('dispo', $dispo)
            ->getQuery()
            ->getResult();
    }
}
