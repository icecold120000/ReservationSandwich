<?php

namespace App\Repository;

use App\Entity\CommandeGroupe;
use App\Entity\Sandwich;
use App\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

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
    public function findAllIndexNonClotureGroupe(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.dateHeureLivraison > :today')
            ->andWhere('c.commandeur = :user')
            ->setParameters(array('today' => new DateTime('now 00:00:00'), 'user' => $user))
            ->orderBy('c.dateHeureLivraison', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return CommandeGroupe[] Returns an array of CommandeGroupe objects
     */
    public function findAllAdminNonClotureGroupe(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.dateHeureLivraison > :today')
            ->setParameter('today', new DateTime('now 00:00:00'))
            ->orderBy('c.dateHeureLivraison', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return CommandeGroupe[] Returns an array of CommandeGroupe objects
     */
    public function exportationCommandeGroupe(string $date): array
    {
        $query = $this->createQueryBuilder('ci');
        $query
            ->andWhere('ci.dateHeureLivraison Like :date')
            ->andWhere('ci.estValide = 1')
            ->setParameter('date', '%' . $date . '%')
            ->orderBy('ci.dateHeureLivraison', 'ASC');

        return $query->getQuery()->getResult();
    }

    /**
     * @return CommandeGroupe[] Returns an array of CommandeGroupe objects
     */
    public function findBySandwich(Sandwich $sandwich, string $date): array
    {
        $query = $this->createQueryBuilder('ci');
        $query
            ->leftJoin('ci.sandwichCommandeGroupe', 'sc')
            ->andWhere('ci.dateHeureLivraison Like :date')
            ->andWhere('ci.estValide = 1')
            ->andWhere('sc.sandwichChoisi = :sandwich')
            ->setParameters(['sandwich' => $sandwich, 'date' => '%' . $date . '%'])
            ->orderBy('ci.dateHeureLivraison', 'ASC');

        return $query->getQuery()->getResult();
    }

    /**
     * @return CommandeGroupe[] Returns an array of CommandeGroupe objects
     */
    public function findByLieuLivraison(string $lieu): array
    {
        $query = $this->createQueryBuilder('ci');
        $query
            ->leftJoin('ci.lieuLivraison', 'll')
            ->andWhere('ll.id = :lieu')
            ->setParameter('lieu', $lieu);

        return $query->getQuery()->getResult();
    }

    /**
     * @return CommandeGroupe[] Returns an array of CommandeGroupe objects
     * @throws Exception
     */
    public function filterIndex(User $user, DateTime $date = null, ?bool $cloture = null): array
    {
        $query = $this->createQueryBuilder('ci');
        $query
            ->leftJoin('ci.commandeur', 'u')
            ->andWhere('u.id = :user')
            ->setParameter('user', $user->getId())
            ->orderBy('ci.dateHeureLivraison', 'ASC');

        if ($date != null) {
            if (new DateTime($date->format('y-m-d') . ' 00:00:00') == new DateTime('now 00:00:00')) {
                $query
                    ->andWhere('ci.dateHeureLivraison Like :date')
                    ->setParameter('date', '%' . $date->format('y-m-d') . '%');
            } else {
                $query
                    ->andWhere('ci.dateHeureLivraison > :dateNow')
                    ->andWhere('ci.dateHeureLivraison < :dateThen')
                    ->setParameters(array('dateNow' => (new DateTime('now 00:00:00'))->format('Y-m-d H:i'),
                        'dateThen' => $date->format('Y-m-d H:i'), 'user' => $user->getId()));
            }
        }

        if ($cloture === false) {
            $query
                ->andWhere('ci.dateHeureLivraison >= :dateNow')
                ->setParameter('dateNow', new DateTime('now'));
        } elseif ($cloture === true) {
            $query
                ->andWhere('ci.dateHeureLivraison < :dateNow')
                ->setParameter('dateNow', new DateTime('now'));
        }

        return $query->getQuery()->getResult();
    }

    /**
     * @return CommandeGroupe[] Returns an array of CommandeGroupe objects
     * @throws Exception
     */
    public function filterAdmin(string $search = null, DateTime $date = null, ?bool $cloture = null): array
    {
        $query = $this->createQueryBuilder('ci');
        $query->orderBy('ci.dateHeureLivraison', 'ASC');

        if ($search != null) {
            $query
                ->leftJoin('ci.commandeur', 'u')
                ->leftJoin('u.eleves', 'e')
                ->leftJoin('u.adultes', 'a')
                ->leftJoin('e.classeEleve', 'cl')
                ->andWhere('u.nomUser LIKE :search OR u.prenomUser LIKE :search 
                OR cl.codeClasse LIKE :search OR cl.libelleClasse LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($date != null) {
            if (new DateTime($date->format('y-m-d') . ' 00:00:00') == new DateTime('now 00:00:00')) {
                $query->andWhere('ci.dateHeureLivraison Like :date')
                    ->setParameter('date', '%' . $date->format('y-m-d') . '%');
            } else {
                $query
                    ->andWhere('ci.dateHeureLivraison > :dateNow')
                    ->andWhere('ci.dateHeureLivraison < :dateThen')
                    ->setParameters(array('dateNow' => (new DateTime('now 00:00:00'))->format('Y-m-d H:i'),
                        'dateThen' => $date->format('Y-m-d H:i'), 'search' => '%' . $search . '%'));
            }
        }

        if ($cloture === true) {
            $query
                ->andWhere('ci.dateHeureLivraison < :dateNow')
                ->setParameter('dateNow', new DateTime('now'));
        } else {
            $query
                ->andWhere('ci.dateHeureLivraison >= :dateNow')
                ->setParameter('dateNow', new DateTime('now'));
        }

        return $query->getQuery()->getResult();
    }
}
