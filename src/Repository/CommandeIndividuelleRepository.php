<?php

namespace App\Repository;

use App\Entity\CommandeIndividuelle;
use App\Entity\User;
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
     * Mettre les commandes individuelles par ordre croissant selon leur dateHeureLivraison
     * @return CommandeIndividuelle[] Returns an array of CommandeIndividuelle objects
     */
    public function findAll(): array
    {
        return $this->findBy(array(), array('dateHeureLivraison' => 'ASC'));
    }

    /**
     * Récupération de toutes les commandes individuelles non clôturées (Administration)
     * @return CommandeIndividuelle[] Returns an array of CommandeIndividuelle objects
     */
    public function findAllNonCloture(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.dateHeureLivraison > :today')
            ->setParameter('today', new DateTime('now 00:00:00'))
            ->orderBy('c.dateHeureLivraison', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les commandes individuelles valides pour être exportées selon une date
     * @param string $date
     * @return CommandeIndividuelle[] Returns an array of CommandeIndividuelle objects
     */
    public function exportationCommande(string $date): array
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
     * Récupération des commandes individuelles selon le sandwich et la date
     * Utilisé pour compter le nombre de sandwichs commandés selon la date
     * @param int $sandwich
     * @param string $date
     * @return CommandeIndividuelle[] Returns an array of CommandeIndividuelle objects
     */
    public function findBySandwich(int $sandwich, string $date): array
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
     * Récupération des commandes individuelles selon la boisson et la date
     * Utilisé pour compter le nombre de boissons commandées selon la date
     * @param int $boisson
     * @param string $date
     * @return CommandeIndividuelle[] Returns an array of CommandeIndividuelle objects
     */
    public function findByBoisson(int $boisson, string $date): array
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
     * Récupération des commandes individuelles selon le dessert et la date
     * Utilisé pour compter le nombre de desserts commandés selon la date
     * @param int $dessert
     * @param string $date
     * @return CommandeIndividuelle[] Returns an array of CommandeIndividuelle objects
     */
    public function findByDessert(int $dessert, string $date): array
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
     * Récupération les commandes individuelles non clôturées selon l'utilisateur
     * (Historique des commandes)
     * @param User $user
     * @return CommandeIndividuelle[] Returns an array of CommandeIndividuelle objects
     */
    public function findIndexAllNonCloture(User $user): array
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
     * Utilisé pour éviter de faire plus d'une commande la même journée
     * @param User $user
     * @param DateTime $date
     * @return CommandeIndividuelle[] Returns an array of CommandeIndividuelle objects
     */
    public function limiteCommande(User $user, DateTime $date): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.commandeur = :user')
            ->andWhere('c.dateHeureLivraison Like :date')
            ->setParameters(array('date' => '%' . $date->format('y-m-d') . '%', 'user' => $user))
            ->orderBy('c.dateHeureLivraison', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Utilisé pour compter les commandes pour les limites
     * (Journalières,Hebdomadaire et Mensuelles)
     * @param User $user
     * @param DateTime $dateDebut
     * @param DateTime $dateFin
     * @return CommandeIndividuelle[] Returns an array of CommandeIndividuelle objects
     */
    public function findBetweenDate(User $user, DateTime $dateDebut, DateTime $dateFin): array
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
     * Filtre des historiques de commandes
     * @param User $user
     * @param DateTime|null $date
     * @param bool|null $cloture
     * @return CommandeIndividuelle[] Returns an array of CommandeIndividuelle objects
     * @throws Exception
     */
    public function filterIndex(User $user, ?DateTime $date = null, ?bool $cloture = null): array
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
                        'dateThen' => $date->format('Y-m-d H:i')));
            }
        }
        if ($cloture === true) {
            $query
                ->andWhere('ci.dateHeureLivraison < :dateNow')
                ->setParameter('dateNow', new DateTime('now'));
        }

        return $query->getQuery()->getResult();
    }

    /**
     * Filtre de la page de gestion des commandes (Administrateur)
     * @param string|null $search
     * @param DateTime|null $date
     * @param bool|null $cloture
     * @return CommandeIndividuelle[] Returns an array of CommandeIndividuelle objects
     * @throws Exception
     */
    public function filterAdmin(?string $search = null, ?DateTime $date = null, ?bool $cloture = null): array
    {
        $query = $this->createQueryBuilder('ci');
        $query->orderBy('ci.dateHeureLivraison', 'ASC');

        if ($search !== null) {
            $query
                ->leftJoin('ci.commandeur', 'u')
                ->leftJoin('u.eleves', 'e')
                ->leftJoin('u.adultes', 'a')
                ->leftJoin('e.classeEleve', 'cl')
                ->andWhere('u.nomUser LIKE :search OR u.prenomUser LIKE :search 
                 OR cl.codeClasse LIKE :search OR cl.libelleClasse LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($date !== null) {
            if (new DateTime($date->format('y-m-d') . ' 00:00:00') == new DateTime('now 00:00:00')) {
                $query->andWhere('ci.dateHeureLivraison Like :date')
                    ->setParameter('date', '%' . $date->format('y-m-d') . '%');
            } else {
                $query
                    ->andWhere('ci.dateHeureLivraison > :dateNow')
                    ->andWhere('ci.dateHeureLivraison < :dateThen')
                    ->setParameters(array('dateNow' => (new DateTime('now 00:00:00'))->format('Y-m-d H:i'),
                        'dateThen' => $date->format('Y-m-d H:i')));
            }
        }

        if ($cloture === true) {
            $query
                ->andWhere('ci.dateHeureLivraison < :dateNow')
                ->setParameter('dateNow', new DateTime('now'));
        }

        return $query->getQuery()->getResult();
    }
}
