<?php

namespace App\Entity;

use App\Repository\AdulteRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AdulteRepository::class)
 */
class Adulte
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
    private ?string $nomAdulte;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $prenomAdulte;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $dateNaissance;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $archiveAdulte;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="adultes", cascade={"remove"})
     */
    private ?User $compteAdulte;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $codeBarreAdulte;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomAdulte(): ?string
    {
        return $this->nomAdulte;
    }

    public function setNomAdulte(?string $nomAdulte): self
    {
        $this->nomAdulte = $nomAdulte;

        return $this;
    }

    public function getPrenomAdulte(): ?string
    {
        return $this->prenomAdulte;
    }

    public function setPrenomAdulte(?string $prenomAdulte): self
    {
        $this->prenomAdulte = $prenomAdulte;

        return $this;
    }

    public function getDateNaissance(): ?DateTime
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?DateTime $dateNaissance): self
    {
        $this->dateNaissance = $dateNaissance;

        return $this;
    }

    public function getArchiveAdulte(): ?bool
    {
        return $this->archiveAdulte;
    }

    public function setArchiveAdulte(?bool $archiveAdulte): self
    {
        $this->archiveAdulte = $archiveAdulte;

        return $this;
    }

    public function getCompteAdulte(): ?User
    {
        return $this->compteAdulte;
    }

    public function setCompteAdulte(?User $compteAdulte): self
    {
        $this->compteAdulte = $compteAdulte;

        return $this;
    }

    public function getCodeBarreAdulte(): ?string
    {
        return $this->codeBarreAdulte;
    }

    public function setCodeBarreAdulte(?string $codeBarreAdulte): self
    {
        $this->codeBarreAdulte = $codeBarreAdulte;

        return $this;
    }
}
