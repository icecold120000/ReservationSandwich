<?php

namespace App\Entity;

use App\Repository\CommandeIndividuelleRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CommandeIndividuelleRepository::class)
 */
class CommandeIndividuelle
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity=Sandwich::class, inversedBy="commandeIndividuelles")
     * @ORM\JoinColumn(nullable=false)
     */
    private $sandwichChoisi;

    /**
     * @ORM\ManyToOne(targetEntity=Boisson::class, inversedBy="commandeIndividuelles")
     * @ORM\JoinColumn(nullable=false)
     */
    private $boissonChoisie;

    /**
     * @ORM\ManyToOne(targetEntity=Dessert::class, inversedBy="commandeIndividuelles")
     * @ORM\JoinColumn(nullable=false)
     */
    private $dessertChoisi;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $prendreChips;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dateHeureLivraison;

    /**
     * @ORM\Column(type="text")
     */
    private ?string $raisonCommande;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="commandeIndividuelles")
     */
    private $commandeur;

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

    public function getBoissonChoisie(): ?Boisson
    {
        return $this->boissonChoisie;
    }

    public function setBoissonChoisie(?Boisson $boissonChoisie): self
    {
        $this->boissonChoisie = $boissonChoisie;

        return $this;
    }

    public function getDessertChoisi(): ?Dessert
    {
        return $this->dessertChoisi;
    }

    public function setDessertChoisi(?Dessert $dessertChoisi): self
    {
        $this->dessertChoisi = $dessertChoisi;

        return $this;
    }

    public function getPrendreChips(): ?bool
    {
        return $this->prendreChips;
    }

    public function setPrendreChips(bool $prendreChips): self
    {
        $this->prendreChips = $prendreChips;

        return $this;
    }

    public function getDateHeureLivraison(): ?\DateTimeInterface
    {
        return $this->dateHeureLivraison;
    }

    public function setDateHeureLivraison(\DateTimeInterface $dateHeureLivraison): self
    {
        $this->dateHeureLivraison = $dateHeureLivraison;

        return $this;
    }

    public function getRaisonCommande(): ?string
    {
        return $this->raisonCommande;
    }

    public function setRaisonCommande(string $raisonCommande): self
    {
        $this->raisonCommande = $raisonCommande;

        return $this;
    }

    public function getCommandeur(): ?User
    {
        return $this->commandeur;
    }

    public function setCommandeur(?User $commandeur): self
    {
        $this->commandeur = $commandeur;

        return $this;
    }
}
