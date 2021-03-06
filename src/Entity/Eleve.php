<?php

namespace App\Entity;

use App\Repository\EleveRepository;
use DateTime;
use DateTimeInterface;
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
    private int $id;

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
    private ?DateTime $dateNaissance;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $archiveEleve;

    /**
     * @ORM\ManyToOne(targetEntity=Classe::class, inversedBy="eleves", cascade={"persist"})
     */
    private ?Classe $classeEleve;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="eleves", cascade={"remove"})
     */
    private ?User $compteEleve;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $photoEleve;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $nbRepas;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $codeBarreEleve;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomEleve(): ?string
    {
        return $this->nomEleve;
    }

    public function setNomEleve(?string $nomEleve): self
    {
        $this->nomEleve = $nomEleve;

        return $this;
    }

    public function getPrenomEleve(): ?string
    {
        return $this->prenomEleve;
    }

    public function setPrenomEleve(?string $prenomEleve): self
    {
        $this->prenomEleve = $prenomEleve;

        return $this;
    }

    public function getDateNaissance(): ?DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?DateTime $dateNaissance): self
    {
        $this->dateNaissance = $dateNaissance;

        return $this;
    }

    public function getArchiveEleve(): ?bool
    {
        return $this->archiveEleve;
    }

    public function setArchiveEleve(?bool $archiveEleve): self
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

    public function getNbRepas(): ?int
    {
        return $this->nbRepas;
    }

    public function setNbRepas(?int $nbRepas): self
    {
        $this->nbRepas = $nbRepas;

        return $this;
    }

    public function getCodeBarreEleve(): ?string
    {
        return $this->codeBarreEleve;
    }

    public function setCodeBarreEleve(?string $codeBarreEleve): self
    {
        $this->codeBarreEleve = $codeBarreEleve;

        return $this;
    }

}
