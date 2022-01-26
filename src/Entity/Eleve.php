<?php

namespace App\Entity;

use App\Repository\EleveRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EleveRepository::class)
 */
class Eleve
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $nomEleve;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $prenomEleve;

    /**
     * @ORM\Column(type="datetime")
     */
    private ?\DateTime $dateNaissance;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $archiveEleve;

    /**
     * @ORM\ManyToOne(targetEntity=Classe::class, inversedBy="eleves")
     */
    private $classeEleve;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="eleves")
     */
    private $compteEleve;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $photoEleve;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomEleve(): ?string
    {
        return $this->nomEleve;
    }

    public function setNomEleve(string $nomEleve): self
    {
        $this->nomEleve = $nomEleve;

        return $this;
    }

    public function getPrenomEleve(): ?string
    {
        return $this->prenomEleve;
    }

    public function setPrenomEleve(string $prenomEleve): self
    {
        $this->prenomEleve = $prenomEleve;

        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(\DateTime $dateNaissance): self
    {
        $this->dateNaissance = $dateNaissance;

        return $this;
    }

    public function getArchiveEleve(): ?bool
    {
        return $this->archiveEleve;
    }

    public function setArchiveEleve(bool $archiveEleve): self
    {
        $this->archiveEleve = $archiveEleve;

        return $this;
    }

    public function getClasseEleve(): ?Classe
    {
        return $this->classeEleve;
    }

    public function setClasseEleve(?Classe $classeEleve): self
    {
        $this->classeEleve = $classeEleve;

        return $this;
    }

    public function getCompteEleve(): ?User
    {
        return $this->compteEleve;
    }

    public function setCompteEleve(?User $compteEleve): self
    {
        $this->compteEleve = $compteEleve;

        return $this;
    }

    public function getPhotoEleve(): ?string
    {
        return $this->photoEleve;
    }

    public function setPhotoEleve(?string $photoEleve): self
    {
        $this->photoEleve = $photoEleve;

        return $this;
    }
}
