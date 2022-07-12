<?php

namespace App\Entity;

use App\Repository\SandwichCommandeGroupeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SandwichCommandeGroupeRepository::class)
 */
class SandwichCommandeGroupe
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity=Sandwich::class, inversedBy="sandwichCommandeGroupes", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Sandwich $sandwichChoisi;

    /**
     * @ORM\Column(type="integer")
     */
    private ?int $nombreSandwich;

    /**
     * @ORM\ManyToOne(targetEntity=CommandeGroupe::class,
     *      inversedBy="sandwichCommandeGroupes", cascade={"remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private ?CommandeGroupe $commandeAffecte;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSandwichChoisi(): ?Sandwich
    {
        return $this->sandwichChoisi;
    }

    public function setSandwichChoisi(?Sandwich $sandwichChoisi): self
    {
        $this->sandwichChoisi = $sandwichChoisi;

        return $this;
    }

    public function getNombreSandwich(): ?int
    {
        return $this->nombreSandwich;
    }

    public function setNombreSandwich(?int $nombreSandwich): self
    {
        $this->nombreSandwich = $nombreSandwich;

        return $this;
    }

    public function getCommandeAffecte(): ?CommandeGroupe
    {
        return $this->commandeAffecte;
    }

    public function setCommandeAffecte(?CommandeGroupe $commandeAffecte): self
    {
        $this->commandeAffecte = $commandeAffecte;

        return $this;
    }
}
