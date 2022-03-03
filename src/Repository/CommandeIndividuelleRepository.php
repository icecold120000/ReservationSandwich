<?php

namespace App\Repository;

use App\Entity\CommandeIndividuelle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CommandeIndividuelle|null find($id, $lockMode = null, $lockVersion = null)
 * @method CommandeIndividuelle|null findOneBy(array $criteria, array $orderBy = null)
 * @method CommandeIndividuelle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommandeIndividuelleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommandeIndividuelle::class);
    }

    /**
     * @return CommandeIndividuelle[] Returns an array of CommandeIndividuelle objects
     */
    public function findAll(): array
    {
        return $this->findBy(array(), array('dateHeureLivraison' => 'ASC'));
    }

    /**
     * @return CommandeIndividuelle[] Returns an array of CommandeIndividuelle objects
     */
    public function findAllNonCloture(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.dateHeureLivraison > :val')
            ->setParameter('val', new \DateTime('now'))
            ->orderBy('c.dateHeureLivraison', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @return CommandeIndividuelle[] Returns an array of CommandeIndividuelle objects
     */
    public function exportationCommande($date): array
    {
        $query = $this->createQueryBuilder('ci');
        $query
            ->andWhere('ci.dateHeureLivraison Like :date')
            ->setParameter('date', '%' . $date . '%')
            ->orderBy('ci.dateHeureLivraison', 'ASC')
        ;

        return $query->getQuery()->getResult();
    }

    /**
     * @return CommandeIndividuelle[] Returns an array of CommandeIndividuelle objects
     */
    public function findBySandwich($sandwich, $date): array
    {
        $query = $this->createQueryBuilder('ci');
        $query
            ->leftJoin('ci.sandwichChoisi','sw')
            ->andWhere('sw.id = :sandwich')
            ->andWhere('ci.dateHeureLivraison Like :date')
            ->setParameters(['sandwich'=> $sandwich, 'date' => '%' . $date . '%'])
        ;

        return $query->getQuery()->getResult();
    }

    /**
     * @return CommandeIndividuelle[] Returns an array of CommandeIndividuelle objects
     */
    public function findByBoisson($boisson, $date): array
    {
        $query = $this->createQueryBuilder('ci');
        $query
            ->leftJoin('ci.boissonChoisie','bo')
            ->andWhere('bo.id = :boisson')
            ->andWhere('ci.dateHeureLivraison Like :date')
            ->setParameters(['boisson'=> $boisson, 'date' => '%' . $date . '%'])
        ;

        return $query->getQuery()->getResult();
    }

    /**
     * @return CommandeIndividuelle[] Returns an array of CommandeIndividuelle objects
     */
    public function findByDessert($dessert, $date): array
    {
        $query = $this->createQueryBuilder('ci');
        $query
            ->leftJoin('ci.dessertChoisi','de')
            ->andWhere('de.id = :dessert')
            ->andWhere('ci.dateHeureLivraison Like :date')
            ->setParameters(['dessert'=> $dessert, 'date' => '%' . $date . '%'])
        ;

        return $query->getQuery()->getResult();
    }

    /**
     * @return CommandeIndividuelle[] Returns an array of CommandeIndividuelle objects
     */
    public function findIndexAllNonCloture($user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.dateHeureLivraison > :val')
            ->andWhere('c.commandeur = :val2')
            ->setParameters(array('val' => new \DateTime('now'),'val2' => $user))
            ->orderBy('c.dateHeureLivraison', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @return CommandeIndividuelle[] Returns an array of CommandeIndividuelle objects
     */
    public function filterIndex($user, $date = null, $cloture = false): array
    {
        $query = $this->createQueryBuilder('ci');
        if ($date != null) {
            if ($date == new \DateTime('now')) {
                $query->andWhere('ci.dateHeureLivraison Like :date')
                    ->setParameter('date', '%' . $date . '%')
                    ->orderBy('ci.dateHeureLivraison', 'ASC');
            } else {
                $query->andWhere('ci.dateHeureLivraison Between :dateNow and :dateThen')
                    ->setParameters(array('dateNow' => new \DateTime('now'), 'dateThen' => $date))
                    ->orderBy('ci.dateHeureLivraison', 'ASC');
            }
        } else {
            $query
                ->andWhere('ci.dateHeureLivraison > :date')
                ->andWhere('ci.commandeur = :val2')
                ->setParameters(array('val' => new \DateTime('now'),'val2' => $user))
                ->orderBy('ci.dateHeureLivraison', 'ASC')
            ;
        }

        if ($cloture == false) {
            $query->andWhere('ci.dateHeureLivraison > :date')
                ->setParameter('date',  new \DateTime('now'))
                ->orderBy('ci.dateHeureLivraison', 'ASC')
            ;
        }
        else {
            return $this->findAll();
        }
        return $query->getQuery()->getResult();
    }

    /**
     * @return CommandeIndividuelle[] Returns an array of CommandeIndividuelle objects
     */
    public function filterAdmin($nom = null, $date = null, $cloture = false): array
    {
        $query = $this->createQueryBuilder('ci');
        if($nom != null){
            $query
                ->leftJoin('ci.commandeur','u')
                ->leftJoin('u.eleves','e')
                ->leftJoin('e.classeEleve','cl')
                ->andWhere('u.nomUser LIKE :nom OR u.prenomUser LIKE :nom
                 OR cl.codeClasse LIKE :nom OR cl.libelleClasse LIKE :nom')
                ->setParameter('nom', '%'.$nom.'%')
                ->orderBy('ci.dateHeureLivraison', 'ASC')
            ;

        }
        else{
            $query->andWhere('ci.dateHeureLivraison > :date')
                ->setParameter('date', new \DateTime('now'))
                ->orderBy('ci.dateHeureLivraison', 'ASC')
            ;
        }
        if ($date != null) {
            if ($date == new \DateTime('now')) {
                $query->andWhere('ci.dateHeureLivraison Like :date')
                    ->setParameter('date', '%'.$date.'%')
                    ->orderBy('ci.dateHeureLivraison', 'ASC')
                ;
            }
            else {
                $query->andWhere('ci.dateHeureLivraison Between :dateNow and :dateThen')
                    ->setParameters(array('dateNow' => new \DateTime('now'),'dateThen' => $date))
                    ->orderBy('ci.dateHeureLivraison', 'ASC')
                ;
            }
        }
        else{
            $query->andWhere('ci.dateHeureLivraison > :date')
                ->setParameter('date', new \DateTime('now'))
                ->orderBy('ci.dateHeureLivraison', 'ASC')
            ;
        }


        if ($cloture == false) {
            $query->andWhere('ci.dateHeureLivraison > :date')
                ->setParameter('date',  new \DateTime('now'))
                ->orderBy('ci.dateHeureLivraison', 'ASC')
            ;
        }
        else {
            return $this->findAll();
        }

        return $query->getQuery()->getResult();
    }


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
    public function findOneBySomeField($value): ?CommandeIndividuelle
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
