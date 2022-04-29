<?php

namespace App\Repository;

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
     * @return InscriptionCantine[] Returns an array of InscriptionCantine objects
     */
    public function findByArchive($value): array
    {
        return $this->createQueryBuilder('ic')
            ->andWhere('ic.archiveInscription = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $value
     * @return InscriptionCantine|null Returns an InscriptionCantine object
     * @throws NonUniqueResultException
     */
    public function findOneByEleve($value): ?InscriptionCantine
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.eleve = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
