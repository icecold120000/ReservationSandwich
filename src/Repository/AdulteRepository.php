<?php

namespace App\Repository;

use App\Entity\Adulte;
use App\Entity\User;
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
    public function findByArchive(bool $archive): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.archiveAdulte = :archive')
            ->setParameter('archive', $archive)
            ->orderBy('a.prenomAdulte', 'ASC')
            ->addGroupBy('a.nomAdulte')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Adulte[] Returns an array of Adulte objects
     */
    public function filter(string $search = null, string $ordreNom = null,
                           string $ordrePrenom = null, bool $archive = null): array
    {
        $query = $this->createQueryBuilder('a');
        if ($search != null) {
            $query->andWhere('a.nomAdulte LIKE :search OR a.prenomAdulte LIKE :search
                OR a.id LIKE :search')
                ->setParameter('search', '%' . $search . '%');
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
     * @param string $nom
     * @param string $prenom
     * @param \DateTime|null $birthday
     * @return Adulte|null
     * @throws NonUniqueResultException
     */
    public function findByNomPrenomDateNaissance(string    $nom, string $prenom,
                                                 \DateTime $birthday = null): ?Adulte
    {
        $query = $this->createQueryBuilder('a');
        $query->andWhere('a.nomAdulte = :nom')
            ->andWhere('a.prenomAdulte = :prenom')
            ->setParameters(array('nom' => $nom, 'prenom' => $prenom));
        if ($birthday != null) {
            $query->andWhere('a.dateNaissance = :birthday')
                ->setParameter('birthday', $birthday);
        }

        return $query->getQuery()->getOneOrNullResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findByNomPrenom(string $nom, string $prenom): ?Adulte
    {
        $query = $this->createQueryBuilder('a');
        $query->andWhere('a.nomAdulte = :nom')
            ->andWhere('a.prenomAdulte = :prenom')
            ->setParameters(array('nom' => $nom, 'prenom' => $prenom));

        return $query->getQuery()->getOneOrNullResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findOneByCompte(User $user): ?Adulte
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.compteAdulte = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
