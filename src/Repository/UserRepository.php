<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use function get_class;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    /**
     * @return User[]
     */
    public function search(string $roleUser = null, bool $userVerifie = null,
                           string $ordreNom = null, string $ordrePrenom = null): array
    {
        $query = $this->createQueryBuilder('u');
        if ($roleUser !== null) {
            $query->andWhere('u.roles like :role')
                ->setParameter('role', '%' . $roleUser . '%');
        }

        if ($userVerifie !== null) {
            $query->andWhere('u.isVerified = :userVerifie')
                ->setParameter('userVerifie', $userVerifie);
        }

        if ($ordreNom !== null && $ordrePrenom !== null) {
            $query->addOrderBy('u.nomUser', $ordreNom)
                ->addOrderBy('u.prenomUser', $ordrePrenom);
        } elseif ($ordreNom == null && $ordrePrenom !== null) {
            $query->orderBy('u.prenomUser', $ordrePrenom);
        } elseif ($ordrePrenom == null && $ordreNom !== null) {
            $query->orderBy('u.nomUser', $ordreNom);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findOneByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findOneByEmailAndDate(string $email, \DateTime $date): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->andWhere('u.dateNaissanceUser = :date')
            ->setParameters(array('email' => $email, 'date' => $date))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findOneByAdulte(int $adulteId): ?User
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.adultes', 'a')
            ->andWhere('a.id = :idAdulte')
            ->setParameter('idAdulte', $adulteId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findOneByEleve(int $eleveId): ?User
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.eleves', 'e')
            ->andWhere('e.id = :idEleve')
            ->setParameter('idEleve', $eleveId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return User[]
     */
    public function findByNomAndPrenom(string $nom, string $prenom): array
    {
        $query = $this->createQueryBuilder('u');
        $query->andWhere('u.nomUser Like :nom')
            ->andWhere('u.prenomUser Like :prenom')
            ->setParameters(array('nom' => '%' . $nom . '%', 'prenom' => '%' . $prenom . '%'));
        return $query->getQuery()->getResult();
    }

    /**
     * @return User[]
     */
    public function findByNomPrenomAndBirthday(string    $nom,
                                               string    $prenom,
                                               \DateTime $birthday): array
    {
        $query = $this->createQueryBuilder('u');
        $query->andWhere('u.nomUser like :nom')
            ->andWhere('u.prenomUser like :prenom')
            ->andWhere('u.dateNaissanceUser = :birthday')
            ->setParameters(array('nom' => '%' . $nom . '%',
                'prenom' => '%' . $prenom . '%', 'birthday' => $birthday));

        return $query->getQuery()->getResult();
    }

    /**
     * Utilisé dans ProfileController
     * @throws NonUniqueResultException
     */
    public function findOneByToken(string $tokenHash): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.tokenHash = :token')
            ->setParameter('token', $tokenHash)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
