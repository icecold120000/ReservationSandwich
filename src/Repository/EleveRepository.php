<?php

namespace App\Repository;

use App\Entity\Eleve;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Eleve|null find($id, $lockMode = null, $lockVersion = null)
 * @method Eleve|null findOneBy(array $criteria, array $orderBy = null)
 * @method Eleve[]    findAll()
 * @method Eleve[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EleveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Eleve::class);
    }

    /**
     * @return Eleve[] Returns an array of Eleve objects
     */
    public function orderByEleve($ordreNom = null, $ordrePrenom = null) : array
    {
        $query = $this->createQueryBuilder('el');
        if ($ordreNom != null && $ordrePrenom != null) {
            $query->addOrderBy('el.nomEleve',$ordreNom)
                ->addOrderBy('el.prenomEleve', $ordrePrenom);
        }
        elseif ($ordreNom == null && $ordrePrenom != null) {
            $query->orderBy('el.prenomEleve', $ordrePrenom);
        }
        elseif ($ordrePrenom == null && $ordreNom != null) {
            $query->orderBy('el.nomEleve', $ordreNom);
        }
        else {
            $this->findAllWithClasse();
        }

        return $query->getQuery()->getResult();
    }

    /**
     * @return Eleve[] Returns an array of Eleve objects
     */
    public function findAllWithClasse(): array
    {
        return $this->createQueryBuilder('el')
            ->leftJoin('el.classe', 'cl')
            ->andWhere('cl.libelle != :val')
            ->setParameter('val', "Quitter l'établissement")
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @return Eleve[] Returns an array of Eleve objects
     */
    public function findByClasse(?int $intClasse, $ordreNom = null, $ordrePrenom = null): array
    {
        $query = $this->createQueryBuilder('el')
            ->andWhere('el.classe = :val')
            ->setParameter('val', $intClasse)
        ;
        if ($ordreNom != null && $ordrePrenom != null) {
            $query->addOrderBy('el.nomEleve',$ordreNom)
                ->addOrderBy('el.prenomEleve', $ordrePrenom);
        }
        elseif ($ordreNom == null && $ordrePrenom != null) {
            $query->orderBy('el.prenomEleve', $ordrePrenom);
        }
        elseif ($ordrePrenom == null && $ordreNom != null) {
            $query->orderBy('el.nomEleve', $ordreNom);
        }
        else {
            $this->findAllWithClasse();
        }

        return $query->getQuery()->getResult();
    }

    // /**
    //  * @return Eleve[] Returns an array of Eleve objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Eleve
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
