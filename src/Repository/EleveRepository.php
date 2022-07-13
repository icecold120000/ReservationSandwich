<?php

namespace App\Repository;

use App\Entity\Classe;
use App\Entity\Eleve;
use App\Entity\User;
use DateTime;
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
     * Récupère les élèves selon s'ils sont archivés ou non
     * @param bool $archive
     * @return Eleve[] Returns an array of Eleve objects
     */
    public function findByArchive(bool $archive): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.archiveEleve = :archive')
            ->setParameter('archive', $archive)
            ->orderBy('e.nomEleve', 'ASC')
            ->addOrderBy('e.prenomEleve', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Filtre de la page de détail d'une classe
     * @param string|null $ordreNom
     * @param string|null $ordrePrenom
     * @param Classe|null $classe
     * @return Eleve[] Returns an array of Eleve objects
     */
    public function orderByEleve(string $ordreNom = null, string $ordrePrenom = null,
                                 Classe $classe = null): array
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
     * Filtre des élèves
     * @param string|null $search
     * @param Classe|null $classe
     * @param bool|null $archive
     * @param string|null $ordreNom
     * @param string|null $ordrePrenom
     * @return Eleve[] Returns an array of Eleve objects
     */
    public function findByClasse(?string $search = null, ?Classe $classe = null, ?bool $archive = null,
                                 ?string $ordreNom = null, ?string $ordrePrenom = null): array
    {
        $query = $this->createQueryBuilder('el');
        if ($search !== null) {
            $query->andWhere('el.nomEleve LIKE :search OR el.prenomEleve LIKE :search
                OR el.id LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($classe !== null) {
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
     * Récupère l'élève selon son nom, prénom et date de naissance
     * @param string $nom
     * @param string $prenom
     * @param DateTime|null $birthday
     * @return Eleve|null
     * @throws NonUniqueResultException
     */
    public function findByNomPrenomDateNaissance(string   $nom, string $prenom,
                                                 DateTime $birthday = null): ?Eleve
    {
        $query = $this->createQueryBuilder('e');
        $query->andWhere('e.nomEleve = :nom')
            ->andWhere('e.prenomEleve = :prenom')
            ->setParameters(array('nom' => $nom, 'prenom' => $prenom));
        if ($birthday != null) {
            $query->andWhere('e.dateNaissance = :birthday')
                ->setParameter('birthday', $birthday);
        }

        return $query->getQuery()->getOneOrNullResult();
    }

    /**
     * Récupère l'élève selon son compte utilisateur
     * @param User $user
     * @return Eleve|null
     * @throws NonUniqueResultException
     */
    public function findOneByCompte(User $user): ?Eleve
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.compteEleve = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
