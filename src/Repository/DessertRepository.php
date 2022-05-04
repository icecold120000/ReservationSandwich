<?php

namespace App\Repository;

use App\Entity\Dessert;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Dessert|null find($id, $lockMode = null, $lockVersion = null)
 * @method Dessert|null findOneBy(array $criteria, array $orderBy = null)
 * @method Dessert[]    findAll()
 * @method Dessert[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DessertRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dessert::class);
    }

    /**
     * @param int|null $dispo
     * @param string $order
     * @return Dessert[] Returns an array of Dessert objects
     */
    public function filter(int $dispo = null, string $order = 'ASC'): array
    {
        $query = $this->createQueryBuilder('d');
        if ($dispo !== null) {
            $query->andWhere('d.dispoDessert = :dispo')
                ->setParameter('dispo', $dispo);
        }
        return $query->orderBy('d.nomDessert', $order)->getQuery()->getResult();
    }

    /**
     * @return Dessert[] Returns an array of Dessert objects
     */
    public function findByDispo(bool $dispo): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.dispoDessert = :dispo')
            ->setParameter('dispo', $dispo)
            ->getQuery()
            ->getResult();
    }
}
