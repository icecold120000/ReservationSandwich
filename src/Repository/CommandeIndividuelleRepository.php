<?php

namespace App\Repository;

use App\Entity\CommandeIndividuelle;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

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
            ->setParameter('val', new DateTime('now 00:00:00'))
            ->orderBy('c.dateHeureLivraison', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return CommandeIndividuelle[] Returns an array of CommandeIndividuelle objects
     */
    public function exportationCommande($date): array
    {
        $query = $this->createQueryBuilder('ci');
        $query
            ->andWhere('ci.dateHeureLivraison Like :date')
            ->andWhere('ci.est_valide = 1')
            ->setParameter('date', '%' . $date . '%')
            ->orderBy('ci.dateHeureLivraison', 'ASC');

        return $query->getQuery()->getResult();
    }

    /**
     * @return CommandeIndividuelle[] Returns an array of CommandeIndividuelle objects
     */
    public function findBySandwich($sandwich, $date): array
    {
        $query = $this->createQueryBuilder('ci');
        $query
            ->leftJoin('ci.sandwichChoisi', 'sw')
            ->andWhere('sw.id = :sandwich')
            ->andWhere('ci.dateHeureLivraison Like :date')
            ->setParameters(['sandwich' => $sandwich, 'date' => '%' . $date . '%']);

        return $query->getQuery()->getResult();
    }

    /**
     * @return CommandeIndividuelle[] Returns an array of CommandeIndividuelle objects
     */
    public function findByBoisson($boisson, $date): array
    {
        $query = $this->createQueryBuilder('ci');
        $query
            ->leftJoin('ci.boissonChoisie', 'bo')
            ->andWhere('bo.id = :boisson')
            ->andWhere('ci.dateHeureLivraison Like :date')
            ->setParameters(['boisson' => $boisson, 'date' => '%' . $date . '%']);

        return $query->getQuery()->getResult();
    }

    /**
     * @return CommandeIndividuelle[] Returns an array of CommandeIndividuelle objects
     */
    public function findByDessert($dessert, $date): array
    {
        $query = $this->createQueryBuilder('ci');
        $query
            ->leftJoin('ci.dessertChoisi', 'de')
            ->andWhere('de.id = :dessert')
            ->andWhere('ci.dateHeureLivraison Like :date')
            ->setParameters(['dessert' => $dessert, 'date' => '%' . $date . '%']);

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
            ->setParameters(array('val' => new DateTime('now 00:00:00'), 'val2' => $user))
            ->orderBy('c.dateHeureLivraison', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return CommandeIndividuelle[] Returns an array of CommandeIndividuelle objects
     */
    public function findBetweenDate($user, $dateDebut, $dateFin): array
    {
        $query = $this->createQueryBuilder('ci');
        $query
            ->andWhere('ci.commandeur = :user')
            ->andWhere('ci.dateHeureLivraison Between :dateStart and :dateEnd')
            ->setParameters(array('dateStart' => $dateDebut, 'dateEnd' => $dateFin, 'user' => $user))
            ->orderBy('ci.dateHeureLivraison', 'ASC');
        return $query->getQuery()->getResult();
    }

    /**
     * @return CommandeIndividuelle[] Returns an array of CommandeIndividuelle objects
     * @throws Exception
     */
    public function filterIndex($user, $date = null, $cloture = false): array
    {
        $query = $this->createQueryBuilder('ci');

        if ($date != null) {
            if (new DateTime($date->format('y-m-d') . ' 00:00:00') == new DateTime('now 00:00:00')) {
                $query
                    ->andWhere('ci.dateHeureLivraison Like :date')
                    ->andWhere('ci.commandeur = :val2')
                    ->setParameters(array('date' => '%' . $date->format('y-m-d') . '%', 'val2' => $user))
                    ->orderBy('ci.dateHeureLivraison', 'ASC');
            } else {
                $query
                    ->andWhere('ci.dateHeureLivraison Between :dateNow and :dateThen')
                    ->andWhere('ci.commandeur = :val2')
                    ->setParameters(array('dateNow' => new DateTime('now 00:00:00'), 'dateThen' => $date->format('y-m-d h:i'), 'val2' => $user))
                    ->orderBy('ci.dateHeureLivraison', 'ASC');
            }
        }

        if ($cloture !== false) {
            $query
                ->andWhere('ci.dateHeureLivraison < :date')
                ->andWhere('ci.commandeur = :user')
                ->setParameters(array('date' => new DateTime('now'), 'user' => $user))
                ->orderBy('ci.dateHeureLivraison', 'ASC');
        } else {
            $query
                ->andWhere('ci.dateHeureLivraison > :date')
                ->andWhere('ci.commandeur = :user')
                ->setParameters(array('date' => new DateTime('now'), 'user' => $user))
                ->orderBy('ci.dateHeureLivraison', 'ASC');
        }
        return $query->getQuery()->getResult();
    }

    /**
     * @return CommandeIndividuelle[] Returns an array of CommandeIndividuelle objects
     * @throws Exception
     */
    public function filterAdmin($nom = null, $date = null, $cloture = null): array
    {
        $query = $this->createQueryBuilder('ci');
        if ($nom != null) {
            $query
                ->leftJoin('ci.commandeur', 'u')
                ->leftJoin('u.eleves', 'e')
                ->leftJoin('e.classeEleve', 'cl')
                ->andWhere('u.nomUser LIKE :nom OR u.prenomUser LIKE :nom
                 OR cl.codeClasse LIKE :nom OR cl.libelleClasse LIKE :nom')
                ->setParameter('nom', '%' . $nom . '%')
                ->orderBy('ci.dateHeureLivraison', 'ASC');
        }

        if ($date != null) {
            if (new DateTime($date->format('y-m-d') . ' 00:00:00') == new DateTime('now 00:00:00')) {
                $query->andWhere('ci.dateHeureLivraison Like :date')
                    ->setParameter('date', '%' . $date->format('y-m-d') . '%')
                    ->orderBy('ci.dateHeureLivraison', 'ASC');
            } else {
                $query->andWhere('ci.dateHeureLivraison Between :dateNow and :dateThen')
                    ->setParameters(array('dateNow' => new DateTime('now 00:00:00'), 'dateThen' => $date))
                    ->orderBy('ci.dateHeureLivraison', 'ASC');
            }
        }

        if ($cloture !== false) {
            $query->andWhere('ci.dateHeureLivraison < :date')
                ->setParameter('date', new DateTime('now'))
                ->orderBy('ci.dateHeureLivraison', 'ASC');
        } else {
            $query->andWhere('ci.dateHeureLivraison > :date')
                ->setParameter('date', new DateTime('now'))
                ->orderBy('ci.dateHeureLivraison', 'ASC');
        }
        return $query->getQuery()->getResult();
    }
}
