<?php

namespace App\Entity;

use App\Repository\InscriptionCantineRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=InscriptionCantineRepository::class)
 */
class InscriptionCantine
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity=Eleve::class, cascade={"persist", "remove"})
     */
    private $eleve;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $repasJ1;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $repasJ2;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $repasJ3;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $repasJ4;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $repasJ5;

    /**
     * @ORM\Column(type="boolean")
     */
    private $archiveInscription;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEleve(): ?Eleve
    {
        return $this->eleve;
    }

    public function setEleve(?Eleve $eleve): self
    {
        $this->eleve = $eleve;

        return $this;
    }

    public function getRepasJ1(): ?bool
    {
        return $this->repasJ1;
    }

    public function setRepasJ1(?bool $repasJ1): self
    {
        $this->repasJ1 = $repasJ1;

        return $this;
    }

    public function getRepasJ2(): ?bool
    {
        return $this->repasJ2;
    }

    public function setRepasJ2(?bool $repasJ2): self
    {
        $this->repasJ2 = $repasJ2;

        return $this;
    }

    public function getRepasJ3(): ?bool
    {
        return $this->repasJ3;
    }

    public function setRepasJ3(?bool $repasJ3): self
    {
        $this->repasJ3 = $repasJ3;

        return $this;
    }

    public function getRepasJ4(): ?bool
    {
        return $this->repasJ4;
    }

    public function setRepasJ4(?bool $repasJ4): self
    {
        $this->repasJ4 = $repasJ4;

        return $this;
    }

    public function getRepasJ5(): ?bool
    {
        return $this->repasJ5;
    }

    public function setRepasJ5(?bool $repasJ5): self
    {
        $this->repasJ5 = $repasJ5;

        return $this;
    }

    public function getArchiveInscription(): ?bool
    {
        return $this->archiveInscription;
    }

    public function setArchiveInscription(bool $archiveInscription): self
    {
        $this->archiveInscription = $archiveInscription;

        return $this;
    }

}
