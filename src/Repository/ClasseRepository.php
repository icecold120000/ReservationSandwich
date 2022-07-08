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
     * Filtre des classes
     * @param string $order
     * @param string|null $search
     * @return Classe[]
     */
    public function filterClasse(string $order, string $search = null): array
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
     * Récupère la classe selon leur libellé
     * @param string $libelle
     * @return Classe|null
     * @throws NonUniqueResultException
     */
    public function findOneByLibelle(string $libelle): ?Classe
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.libelle = :libelle')
            ->setParameter('libelle', $libelle)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère la classe selon le code
     * @param string $code
     * @return Classe|null
     * @throws NonUniqueResultException
     */
    public function findOneByCode(string $code): ?Classe
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.codeClasse = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
