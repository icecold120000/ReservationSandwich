<?php

namespace App\Repository;

use App\Entity\User;
use DateTime;
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
     * Filtre des utilisateurs
     * @param string|null $roleUser
     * @param bool|null $userVerifie
     * @param string|null $ordreNom
     * @param string|null $ordrePrenom
     * @param string|null $userName
     * @return User[]
     */
    public function search(?string $roleUser = null, ?bool $userVerifie = null,
                           ?string $ordreNom = null, ?string $ordrePrenom = null,
                           ?string $userName = null): array
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

        if ($userName !== null) {
            $query->andWhere('u.nomUser like :name or u.prenomUser like :name')
                ->setParameter('name', '%' . $userName . '%');
        }

        return $query->getQuery()->getResult();
    }

    /**
     * Récupère le compte utilisateur selon l'email
     * @param string $email
     * @return User|null
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
     * Utilisé dans le formulaire d'oubli de mot de passe
     * Pour rechercher si l'utilisateur existe pour lui
     * envoyer un email afin qu'il puisse réinitialiser
     * son mot de passe
     * @param string $email
     * @param DateTime $date
     * @return User|null
     * @throws NonUniqueResultException
     */
    public function findOneByEmailAndDate(string $email, DateTime $date): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->andWhere('u.dateNaissanceUser = :date')
            ->setParameters(array('email' => $email, 'date' => $date))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère le compte utilisateur selon l'identifiant de l'adulte
     * @param int $adulteId
     * @return User|null
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
     * Récupère le compte utilisateur selon l'identifiant de l'élève
     * @param int $eleveId
     * @return User|null
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
     * Vérification si un utilisateur existe (adulte)
     * selon leur nom, prénom et date de naissance
     * @param string $nom
     * @param string $prenom
     * @return User|null
     * @throws NonUniqueResultException
     */
    public function findByNomAndPrenom(string $nom, string $prenom): ?User
    {
        $query = $this->createQueryBuilder('u');
        $query->andWhere('u.nomUser Like :nom')
            ->andWhere('u.prenomUser Like :prenom')
            ->setParameters(array('nom' => '%' . $nom . '%', 'prenom' => '%' . $prenom . '%'));
        return $query->getQuery()->getOneOrNullResult();
    }

    /**
     * Vérification si un utilisateur existe (élève)
     * selon leur nom, prénom et date de naissance
     * @param string $nom
     * @param string $prenom
     * @param DateTime $birthday
     * @return User|null
     * @throws NonUniqueResultException
     */
    public function findByNomPrenomAndBirthday(string   $nom,
                                               string   $prenom,
                                               DateTime $birthday): ?User
    {
        $query = $this->createQueryBuilder('u');
        $query->andWhere('u.nomUser like :nom')
            ->andWhere('u.prenomUser like :prenom')
            ->andWhere('u.dateNaissanceUser = :birthday')
            ->setParameters(array('nom' => '%' . $nom . '%',
                'prenom' => '%' . $prenom . '%', 'birthday' => $birthday));

        return $query->getQuery()->getOneOrNullResult();
    }

    /**
     * Utilisé dans ProfileController @entity
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
