<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @UniqueEntity(fields={"email"}, message="l existe déjà un compte avec cette email")
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    const ROLE_ADULTES = "ROLE_ADULTES";
    const ROLE_ADMIN = "ROLE_ADMIN";
    const ROLE_ELEVE = "ROLE_ELEVE";
    const ROLE_CUISINE = "ROLE_CUISINE";
    const ROLE_USER = "ROLE_USER";

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private ?string $email;

    /**
     * @ORM\Column(type="json")
     */
    private array $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private string $password;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $tokenHash;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $nomUser;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $prenomUser;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $dateNaissanceUser;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $isVerified;

    /**
     * @ORM\OneToMany(targetEntity=Adulte::class, mappedBy="compteAdulte", cascade={"remove"})
     */
    private $adultes;

    /**
     * @ORM\OneToMany(targetEntity=Eleve::class, mappedBy="compteEleve", cascade={"remove"})
     */
    private $eleves;

    /**
     * @ORM\OneToMany(targetEntity=CommandeIndividuelle::class, mappedBy="commandeur", orphanRemoval=true)
     */
    private $commandeIndividuelles;

    /**
     * @ORM\OneToMany(targetEntity=CommandeGroupe::class, mappedBy="commandeur", orphanRemoval=true)
     */
    private $commandeGroupes;


    public function __construct()
    {
        $this->adultes = new ArrayCollection();
        $this->eleves = new ArrayCollection();
        $this->commandeIndividuelles = new ArrayCollection();
        $this->commandeGroupes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string)$this->email;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string)$this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        if ($password === null) {
            $password = $this->getPassword();
        }
        $this->password = $password;
        return $this;
    }

    public function getTokenHash(): ?string
    {
        return $this->tokenHash;
    }

    public function setTokenHash(string $tokenHash): self
    {
        $this->tokenHash = $tokenHash;

        return $this;
    }

    public function getNomUser(): ?string
    {
        return $this->nomUser;
    }

    public function setNomUser(string $nomUser): self
    {
        $this->nomUser = $nomUser;

        return $this;
    }

    public function getPrenomUser(): ?string
    {
        return $this->prenomUser;
    }

    public function setPrenomUser(string $prenomUser): self
    {
        $this->prenomUser = $prenomUser;

        return $this;
    }

    public function getDateNaissanceUser(): ?DateTime
    {
        return $this->dateNaissanceUser;
    }

    public function setDateNaissanceUser(?DateTime $dateNaissanceUser): self
    {
        $this->dateNaissanceUser = $dateNaissanceUser;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function isVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getAdultes(): Collection
    {
        return $this->adultes;
    }

    public function addAdulte(Adulte $adulte): self
    {
        if (!$this->adultes->contains($adulte)) {
            $this->adultes[] = $adulte;
            $adulte->setCompteAdulte($this);
        }

        return $this;
    }

    public function removeAdulte(Adulte $adulte): self
    {
        if ($this->adultes->removeElement($adulte)) {
            // set the owning side to null (unless already changed)
            if ($adulte->getCompteAdulte() === $this) {
                $adulte->setCompteAdulte(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection
     */
    public function getEleves(): Collection
    {
        return $this->eleves;
    }

    public function addEleve(Eleve $eleve): self
    {
        if (!$this->eleves->contains($eleve)) {
            $this->eleves[] = $eleve;
            $eleve->setCompteEleve($this);
        }

        return $this;
    }

    public function removeEleve(Eleve $eleve): self
    {
        if ($this->eleves->removeElement($eleve)) {
            // set the owning side to null (unless already changed)
            if ($eleve->getCompteEleve() === $this) {
                $eleve->setCompteEleve(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection
     */
    public function getCommandeIndividuelles(): Collection
    {
        return $this->commandeIndividuelles;
    }

    public function addCommandeIndividuelle(CommandeIndividuelle $commandeIndividuelle): self
    {
        if (!$this->commandeIndividuelles->contains($commandeIndividuelle)) {
            $this->commandeIndividuelles[] = $commandeIndividuelle;
            $commandeIndividuelle->setCommandeur($this);
        }

        return $this;
    }

    public function removeCommandeIndividuelle(CommandeIndividuelle $commandeIndividuelle): self
    {
        if ($this->commandeIndividuelles->removeElement($commandeIndividuelle)) {
            // set the owning side to null (unless already changed)
            if ($commandeIndividuelle->getCommandeur() === $this) {
                $commandeIndividuelle->setCommandeur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection
     */
    public function getCommandeGroupes(): Collection
    {
        return $this->commandeGroupes;
    }

    public function addCommandeGroupe(CommandeGroupe $commandeGroupe): self
    {
        if (!$this->commandeGroupes->contains($commandeGroupe)) {
            $this->commandeGroupes[] = $commandeGroupe;
            $commandeGroupe->setCommandeur($this);
        }

        return $this;
    }

    public function removeCommandeGroupe(CommandeGroupe $commandeGroupe): self
    {
        if ($this->commandeGroupes->removeElement($commandeGroupe)) {
            // set the owning side to null (unless already changed)
            if ($commandeGroupe->getCommandeur() === $this) {
                $commandeGroupe->setCommandeur(null);
            }
        }

        return $this;
    }

}
