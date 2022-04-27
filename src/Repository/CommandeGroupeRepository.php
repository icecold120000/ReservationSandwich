<?php

namespace App\Repository;

use App\Entity\CommandeGroupe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CommandeGroupe|null find($id, $lockMode = null, $lockVersion = null)
 * @method CommandeGroupe|null findOneBy(array $criteria, array $orderBy = null)
 * @method CommandeGroupe[]    findAll()
 * @method CommandeGroupe[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommandeGroupeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommandeGroupe::class);
    }

    /**
     * @return CommandeGroupe[] Returns an array of CommandeGroupe objects
     */
    public function findAllIndexNonClotureGroupe($user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.dateHeureLivraison > :val')
            ->andWhere('c.commandeur = :val2')
            ->setParameters(array('val' => new \DateTime('now 00:00:00'),'val2' => $user))
            ->orderBy('c.dateHeureLivraison', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @return CommandeGroupe[] Returns an array of CommandeGroupe objects
     */
    public function findAllAdminNonClotureGroupe(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.dateHeureLivraison > :val')
            ->setParameter('val', new \DateTime('now 00:00:00'))
            ->orderBy('c.dateHeureLivraison', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @return CommandeGroupe[] Returns an array of CommandeGroupe objects
     */
    public function exportationCommandeGroupe($date): array
    {
        $query = $this->createQueryBuilder('ci');
        $query
            ->andWhere('ci.dateHeureLivraison Like :date')
            ->andWhere('ci.estValide = 1')
            ->setParameter('date', '%' . $date . '%')
            ->orderBy('ci.dateHeureLivraison', 'ASC')
        ;

        return $query->getQuery()->getResult();
    }

    /**
     * @return CommandeGroupe[] Returns an array of CommandeGroupe objects
     */
    public function findBySandwich($sandwich ,$date): array
    {
        $query = $this->createQueryBuilder('ci');
        $query
            ->leftJoin('ci.sandwichCommandeGroupe','sc')
            ->andWhere('ci.dateHeureLivraison Like :date')
            ->andWhere('ci.estValide = 1')
            ->andWhere('sc.sandwichChoisi = :sandwich')
            ->setParameters(['sandwich' => $sandwich,'date'=>'%' . $date . '%'])
            ->orderBy('ci.dateHeureLivraison', 'ASC')
        ;

        return $query->getQuery()->getResult();
    }

    /**
     * @return CommandeGroupe[] Returns an array of CommandeGroupe objects
     */
    public function findByLieuLivraison($lieu): array
    {
        $query = $this->createQueryBuilder('ci');
        $query
            ->leftJoin('ci.lieuLivraison','ll')
            ->andWhere('ll.id = :lieu')
            ->setParameter('lieu', $lieu)
        ;

        return $query->getQuery()->getResult();
    }


    /**
     * @return CommandeGroupe[] Returns an array of CommandeGroupe objects
     */
    public function filterIndex($user, $date = null, $cloture = false): array
    {
        $query = $this->createQueryBuilder('ci');

        if ($date != null) {
            if ($date == new \DateTime('now')) {
                $query
                    ->andWhere('ci.dateHeureLivraison Like :date')
                    ->andWhere('ci.commandeur = :val2')
                    ->setParameters(array('date'=> '%'. $date->format('y-m-d') .'%', 'val2' => $user))
                    ->orderBy('ci.dateHeureLivraison', 'ASC');
            } else {
                $query
                    ->andWhere('ci.dateHeureLivraison Between :dateNow and :dateThen')
                    ->andWhere('ci.commandeur = :val2')
                    ->setParameters(array('dateNow' => new \DateTime('now 00:00:00'), 'dateThen' => $date->format('y-m-d h:i'),'val2' => $user))
                    ->orderBy('ci.dateHeureLivraison', 'ASC')
                ;
            }
        }

        if ($cloture != false) {
            $query
                ->andWhere('ci.dateHeureLivraison < :date')
                ->andWhere('ci.commandeur = :user')
                ->setParameters(array('date' => new \DateTime('now'),'user' => $user))
                ->orderBy('ci.dateHeureLivraison', 'ASC')
            ;
        }
        else {
            $query
                ->andWhere('ci.dateHeureLivraison > :date')
                ->andWhere('ci.commandeur = :user')
                ->setParameters(array('date' => new \DateTime('now'),'user' => $user))
                ->orderBy('ci.dateHeureLivraison', 'ASC')
            ;
        }
        return $query->getQuery()->getResult();
    }

    /**
     * @return CommandeGroupe[] Returns an array of CommandeGroupe objects
     * @throws \Exception
     */
    public function filterAdmin($nom = null, $date = null, $cloture = false): array
    {
        $query = $this->createQueryBuilder('ci');
        if ($nom != null) {
            $query
                ->leftJoin('ci.commandeur', 'u')
                ->leftJoin('u.eleves', 'e')
                ->leftJoin('e.classeEleve', 'cl')
                ->andWhere('u.nomUser LIKE :nom OR u.prenomUser LIKE :nom
                 OR cl.codeClasse LIKE :nom OR cl.libelleClasse LIKE :nom')
                ->setParameter('nom', '%'. $nom .'%')
                ->orderBy('ci.dateHeureLivraison', 'ASC')
            ;
        }

        if ($date != null) {
            if (new \DateTime($date->format('y-m-d').' 00:00:00') == new \DateTime('now 00:00:00')) {
                $query->andWhere('ci.dateHeureLivraison Like :date')
                    ->setParameter('date', '%'.$date->format('y-m-d').'%')
                    ->orderBy('ci.dateHeureLivraison', 'ASC')
                ;
            }
            else {
                $query->andWhere('ci.dateHeureLivraison Between :dateNow and :dateThen')
                    ->setParameters(array('dateNow' => new \DateTime('now 00:00:00'),'dateThen' => $date))
                    ->orderBy('ci.dateHeureLivraison', 'ASC')
                ;
            }
        }

        if ($cloture != false) {
            $query->andWhere('ci.dateHeureLivraison < :date')
                ->setParameter('date',  new \DateTime('now'))
                ->orderBy('ci.dateHeureLivraison', 'ASC')
            ;
        }
        else {
            $query->andWhere('ci.dateHeureLivraison > :date')
                ->setParameter('date', new \DateTime('now'))
                ->orderBy('ci.dateHeureLivraison', 'ASC')
            ;
        }

        return $query->getQuery()->getResult();
    }

    // /**
    //  * @return CommandeGroupe[] Returns an array of CommandeGroupe objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CommandeGroupe
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

}
