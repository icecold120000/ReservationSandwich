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
    private $nomEleve;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $prenomEleve;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dateNaissance;

    /**
     * @ORM\Column(type="boolean")
     */
    private $archiveEleve;

    /**
     * @ORM\ManyToOne(targetEntity=Classe::class, inversedBy="eleves")
     */
    private $classeEleve;

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

    public function setDateNaissance(\DateTimeInterface $dateNaissance): self
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
}
