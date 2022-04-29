<?php

namespace App\Repository;

use App\Entity\Eleve;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
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
    public function findByArchive($value): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.archiveEleve = :val')
            ->setParameter('val', $value)
            ->orderBy('e.nomEleve', 'ASC')
            ->addOrderBy('e.prenomEleve', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Eleve[] Returns an array of Eleve objects
     */
    public function orderByEleve($ordreNom = null, $ordrePrenom = null, $classe = null): array
    {
        $query = $this->createQueryBuilder('el');
        if ($ordreNom != null && $ordrePrenom != null) {
            $query->addOrderBy('el.nomEleve', $ordreNom)
                ->addOrderBy('el.prenomEleve', $ordrePrenom);
        } elseif ($ordreNom == null && $ordrePrenom != null) {
            $query->orderBy('el.prenomEleve', $ordrePrenom);
        } elseif ($ordrePrenom == null && $ordreNom != null) {
            $query->orderBy('el.nomEleve', $ordreNom);
        }

        if ($classe != null) {
            $query
                ->leftJoin('el.classeEleve', 'c')
                ->andWhere('c.id = :classe')
                ->setParameter('classe', $classe);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * @return Eleve[] Returns an array of Eleve objects
     */
    public function findByClasse($nom = null, $classe = null, $archive = null,
                                 $ordreNom = null, $ordrePrenom = null): array
    {
        $query = $this->createQueryBuilder('el');
        if ($nom != null) {
            $query->andWhere('el.nomEleve LIKE :nom OR el.prenomEleve LIKE :nom
                OR el.id LIKE :nom')
                ->setParameter('nom', '%' . $nom . '%');
        }

        if ($classe != null) {
            $query->leftJoin('el.classeEleve', 'cl');
            $query->andWhere('cl.id = :id')
                ->setParameter('id', $classe);
        }

        if ($archive !== null) {
            $query->andWhere('el.archiveEleve = :archive')
                ->setParameter('archive', $archive);
        }

        if ($ordreNom != null && $ordrePrenom != null) {
            $query->addOrderBy('el.nomEleve', $ordreNom)
                ->addOrderBy('el.prenomEleve', $ordrePrenom);
        } elseif ($ordreNom == null && $ordrePrenom != null) {
            $query->orderBy('el.prenomEleve', $ordrePrenom);
        } elseif ($ordrePrenom == null && $ordreNom != null) {
            $query->orderBy('el.nomEleve', $ordreNom);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * @param $nom
     * @param $prenom
     * @param null $birthday
     * @return Eleve|null
     * @throws NonUniqueResultException
     */
    public function findByNomPrenomDateNaissance($nom, $prenom, $birthday = null): ?Eleve
    {
        $query = $this->createQueryBuilder('e');
        $query->andWhere('e.nomEleve = :val')
            ->andWhere('e.prenomEleve = :val2')
            ->setParameters(array('val' => $nom, 'val2' => $prenom));
        if ($birthday != null) {
            $query->andWhere('e.dateNaissance = :birthday')
                ->setParameter('birthday', $birthday);
        }

        return $query->getQuery()->getOneOrNullResult();
    }

    /**
     * @param $value
     * @return Eleve|null
     * @throws NonUniqueResultException
     */
    public function findOneByCompte($value): ?Eleve
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.compteEleve = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
