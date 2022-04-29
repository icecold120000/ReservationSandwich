<?php

namespace App\Repository;

use App\Entity\Adulte;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
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
            ->getResult();
    }

    /**
     * @return Adulte[] Returns an array of Adulte objects
     */
    public function filter($nom = null, $ordreNom = null, $ordrePrenom = null, $archive = null): array
    {
        $query = $this->createQueryBuilder('a');
        if ($nom != null) {
            $query->andWhere('a.nomAdulte LIKE :nom OR a.prenomAdulte LIKE :nom
                OR a.id LIKE :nom')
                ->setParameter('nom', '%' . $nom . '%');
        }

        if ($ordreNom !== null && $ordrePrenom !== null) {
            $query->addOrderBy('a.nomAdulte', $ordreNom)
                ->addOrderBy('a.prenomAdulte', $ordrePrenom);
        } elseif ($ordreNom == null && $ordrePrenom !== null) {
            $query->orderBy('a.prenomAdulte', $ordrePrenom);
        } elseif ($ordrePrenom == null && $ordreNom !== null) {
            $query->orderBy('a.nomAdulte', $ordreNom);
        }

        if ($archive !== null) {
            $query->andWhere('a.archiveAdulte = :archive')
                ->setParameter('archive', $archive);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * @param $nom
     * @param $prenom
     * @param null $birthday
     * @return Adulte|null
     * @throws NonUniqueResultException
     */
    public function findByNomPrenomDateNaissance($nom, $prenom, $birthday = null): ?Adulte
    {
        $query = $this->createQueryBuilder('a');
        $query->andWhere('a.nomAdulte = :val')
            ->andWhere('a.prenomAdulte = :val2')
            ->setParameters(array('val' => $nom, 'val2' => $prenom));
        if ($birthday != null) {
            $query->andWhere('a.dateNaissance = :birthday')
                ->setParameter('birthday', $birthday);
        }

        return $query->getQuery()->getOneOrNullResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findByNomPrenom($nom, $prenom): ?Adulte
    {
        $query = $this->createQueryBuilder('a');
        $query->andWhere('a.nomAdulte = :val')
            ->andWhere('a.prenomAdulte = :val2')
            ->setParameters(array('val' => $nom, 'val2' => $prenom));

        return $query->getQuery()->getOneOrNullResult();
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
