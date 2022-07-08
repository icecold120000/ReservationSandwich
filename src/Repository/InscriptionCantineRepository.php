<?php

namespace App\Repository;

use App\Entity\Eleve;
use App\Entity\InscriptionCantine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method InscriptionCantine|null find($id, $lockMode = null, $lockVersion = null)
 * @method InscriptionCantine|null findOneBy(array $criteria, array $orderBy = null)
 * @method InscriptionCantine[]    findAll()
 * @method InscriptionCantine[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InscriptionCantineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InscriptionCantine::class);
    }

    /**
     * Récupère toutes les incriptions selon si l'inscription est archivée ou non
     * @param bool $archive
     * @return InscriptionCantine[] Returns an array of InscriptionCantine objects
     */
    public function findByArchive(bool $archive): array
    {
        return $this->createQueryBuilder('ic')
            ->andWhere('ic.archiveInscription = :archive')
            ->setParameter('archive', $archive)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère l'inscription d'un élève
     * @param int $eleve
     * @return InscriptionCantine|null Returns an InscriptionCantine object
     * @throws NonUniqueResultException
     */
    public function findOneByEleve(int $eleve): ?InscriptionCantine
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.eleve = :eleve')
            ->setParameter('eleve', $eleve)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
