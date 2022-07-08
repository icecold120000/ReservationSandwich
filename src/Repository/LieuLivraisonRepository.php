<?php

namespace App\Repository;

use App\Entity\LieuLivraison;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
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
     * Filtre des lieux de livraisons
     * @param bool|null $active
     * @param string $order
     * @return LieuLivraison[] Returns an array of Boisson objects
     */
    public function filter(bool $active = null, string $order = 'ASC'): array
    {
        $query = $this->createQueryBuilder('l');
        if ($active !== null) {
            $query->andWhere('l.estActive = :active')
                ->setParameter('active', $active);
        }
        return $query->orderBy('l.libelleLieu', $order)->getQuery()->getResult();
    }

    /**
     * Récupère le lieu de livraison selon le libellé
     * @param string $libelle
     * @return LieuLivraison|null
     * @throws NonUniqueResultException
     */
    public function findOneByLibelle(string $libelle): ?LieuLivraison
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.libelleLieu = :libelle')
            ->setParameter('libelle', $libelle)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
