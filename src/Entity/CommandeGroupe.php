<?php

namespace App\Entity;

use App\Repository\CommandeGroupeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\Pure;

/**
 * @ORM\Entity(repositoryClass=CommandeGroupeRepository::class)
 */
class CommandeGroupe
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Boisson::class, inversedBy="commandeGroupes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $boissonChoisie;

    /**
     * @ORM\ManyToOne(targetEntity=Dessert::class, inversedBy="commandeGroupes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $dessertChoisi;

    /**
     * @ORM\Column(type="boolean")
     */
    private $prendreChips;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $commentaireCommande;

    /**
     * @ORM\Column(type="text")
     */
    private $motifSortie;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dateHeureLivraison;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $lieuLivraison;

    /**
     * @ORM\OneToMany(targetEntity=SandwichCommandeGroupe::class, mappedBy="commandeAffecte", orphanRemoval=true)
     */
    private $sandwichCommandeGroupes;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dateCreation;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="commandeGroupes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $commandeur;

    /**
     * @ORM\Column(type="boolean")
     */
    private $estValide;

    #[Pure] public function __construct()
    {
        $this->sandwichCommandeGroupes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCommentaireCommande(): ?string
    {
        return $this->commentaireCommande;
    }

    public function setCommentaireCommande(?string $commentaireCommande): self
    {
        $this->commentaireCommande = $commentaireCommande;

        return $this;
    }

    public function getMotifSortie(): ?string
    {
        return $this->motifSortie;
    }

    public function setMotifSortie(string $motifSortie): self
    {
        $this->motifSortie = $motifSortie;

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

    public function getLieuLivraison(): ?string
    {
        return $this->lieuLivraison;
    }

    public function setLieuLivraison(string $lieuLivraison): self
    {
        $this->lieuLivraison = $lieuLivraison;

        return $this;
    }

    /**
     * @return Collection|SandwichCommandeGroupe[]
     */
    public function getSandwichCommandeGroupes(): Collection
    {
        return $this->sandwichCommandeGroupes;
    }

    public function addSandwichCommandeGroupe(SandwichCommandeGroupe $sandwichCommandeGroupe): self
    {
        if (!$this->sandwichCommandeGroupes->contains($sandwichCommandeGroupe)) {
            $this->sandwichCommandeGroupes[] = $sandwichCommandeGroupe;
            $sandwichCommandeGroupe->setCommandeAffecte($this);
        }

        return $this;
    }

    public function removeSandwichCommandeGroupe(SandwichCommandeGroupe $sandwichCommandeGroupe): self
    {
        if ($this->sandwichCommandeGroupes->removeElement($sandwichCommandeGroupe)) {
            // set the owning side to null (unless already changed)
            if ($sandwichCommandeGroupe->getCommandeAffecte() === $this) {
                $sandwichCommandeGroupe->setCommandeAffecte(null);
            }
        }

        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): self
    {
        $this->dateCreation = $dateCreation;

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

    public function getEstValide(): ?bool
    {
        return $this->estValide;
    }

    public function setEstValide(bool $estValide): self
    {
        $this->estValide = $estValide;

        return $this;
    }
}
