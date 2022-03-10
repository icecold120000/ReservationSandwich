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
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Sandwich::class, inversedBy="sandwichCommandeGroupes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $sandwichChoisi;

    /**
     * @ORM\Column(type="integer")
     */
    private $nombreSandwich;

    /**
     * @ORM\ManyToOne(targetEntity=CommandeGroupe::class, inversedBy="sandwichCommandeGroupes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $commandeAffecte;

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

    public function setNombreSandwich(int $nombreSandwich): self
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
