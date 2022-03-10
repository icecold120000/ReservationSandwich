<?php

namespace App\Entity;

use App\Repository\SandwichRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\Pure;

/**
 * @ORM\Entity(repositoryClass=SandwichRepository::class)
 */
class Sandwich
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
    private ?string $nomSandwich;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $imageSandwich;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $ingredientSandwich;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $commentaireSandwich;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $dispoSandwich;

    /**
     * @ORM\OneToMany(targetEntity=CommandeIndividuelle::class, mappedBy="sandwichChoisi")
     */
    private $commandeIndividuelles;

    /**
     * @ORM\OneToMany(targetEntity=SandwichCommandeGroupe::class, mappedBy="sandwichChoisi", orphanRemoval=true)
     */
    private $sandwichCommandeGroupes;

    #[Pure] public function __construct()
    {
        $this->commandeIndividuelles = new ArrayCollection();
        $this->sandwichCommandeGroupes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomSandwich(): ?string
    {
        return $this->nomSandwich;
    }

    public function setNomSandwich(string $nomSandwich): self
    {
        $this->nomSandwich = $nomSandwich;

        return $this;
    }

    public function getImageSandwich(): ?string
    {
        return $this->imageSandwich;
    }

    public function setImageSandwich(string $imageSandwich): self
    {
        $this->imageSandwich = $imageSandwich;

        return $this;
    }

    public function getIngredientSandwich(): ?string
    {
        return $this->ingredientSandwich;
    }

    public function setIngredientSandwich(string $ingredientSandwich): self
    {
        $this->ingredientSandwich = $ingredientSandwich;

        return $this;
    }

    public function getCommentaireSandwich(): ?string
    {
        return $this->commentaireSandwich;
    }

    public function setCommentaireSandwich(string $commentaireSandwich): self
    {
        $this->commentaireSandwich = $commentaireSandwich;

        return $this;
    }

    public function getDispoSandwich(): ?bool
    {
        return $this->dispoSandwich;
    }

    public function setDispoSandwich(bool $dispoSandwich): self
    {
        $this->dispoSandwich = $dispoSandwich;

        return $this;
    }

    /**
     * @return Collection|CommandeIndividuelle[]
     */
    public function getCommandeIndividuelles(): Collection
    {
        return $this->commandeIndividuelles;
    }

    public function addCommandeIndividuelle(CommandeIndividuelle $commandeIndividuelle): self
    {
        if (!$this->commandeIndividuelles->contains($commandeIndividuelle)) {
            $this->commandeIndividuelles[] = $commandeIndividuelle;
            $commandeIndividuelle->setSandwichChoisi($this);
        }

        return $this;
    }

    public function removeCommandeIndividuelle(CommandeIndividuelle $commandeIndividuelle): self
    {
        if ($this->commandeIndividuelles->removeElement($commandeIndividuelle)) {
            // set the owning side to null (unless already changed)
            if ($commandeIndividuelle->getSandwichChoisi() === $this) {
                $commandeIndividuelle->setSandwichChoisi(null);
            }
        }

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
            $sandwichCommandeGroupe->setSandwichChoisi($this);
        }

        return $this;
    }

    public function removeSandwichCommandeGroupe(SandwichCommandeGroupe $sandwichCommandeGroupe): self
    {
        if ($this->sandwichCommandeGroupes->removeElement($sandwichCommandeGroupe)) {
            // set the owning side to null (unless already changed)
            if ($sandwichCommandeGroupe->getSandwichChoisi() === $this) {
                $sandwichCommandeGroupe->setSandwichChoisi(null);
            }
        }

        return $this;
    }
}
