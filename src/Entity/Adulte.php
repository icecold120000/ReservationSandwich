<?php

namespace App\Entity;

use App\Repository\AdulteRepository;
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
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nomAdulte;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $prenomAdulte;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dateNaissance;

    /**
     * @ORM\Column(type="boolean")
     */
    private $archiveAdulte;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomAdulte(): ?string
    {
        return $this->nomAdulte;
    }

    public function setNomAdulte(string $nomAdulte): self
    {
        $this->nomAdulte = $nomAdulte;

        return $this;
    }

    public function getPrenomAdulte(): ?string
    {
        return $this->prenomAdulte;
    }

    public function setPrenomAdulte(string $prenomAdulte): self
    {
        $this->prenomAdulte = $prenomAdulte;

        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(\DateTimeInterface $dateNaissance): self
    {
        $this->dateNaissance = $dateNaissance;

        return $this;
    }

    public function getArchiveAdulte(): ?bool
    {
        return $this->archiveAdulte;
    }

    public function setArchiveAdulte(bool $archiveAdulte): self
    {
        $this->archiveAdulte = $archiveAdulte;

        return $this;
    }
}
