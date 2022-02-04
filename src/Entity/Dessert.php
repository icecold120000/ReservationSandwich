<?php

namespace App\Entity;

use App\Repository\DessertRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DessertRepository::class)
 */
class Dessert
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
    private ?string $nomDessert;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $imageDessert;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $ingredientDessert;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $commentaireDessert;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $dispoDessert;

    /**
     * @ORM\OneToMany(targetEntity=CommandeIndividuelle::class, mappedBy="dessertChoisi")
     */
    private $commandeIndividuelles;

    public function __construct()
    {
        $this->commandeIndividuelles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomDessert(): ?string
    {
        return $this->nomDessert;
    }

    public function setNomDessert(string $nomDessert): self
    {
        $this->nomDessert = $nomDessert;

        return $this;
    }

    public function getImageDessert(): ?string
    {
        return $this->imageDessert;
    }

    public function setImageDessert(string $imageDessert): self
    {
        $this->imageDessert = $imageDessert;

        return $this;
    }

    public function getIngredientDessert(): ?string
    {
        return $this->ingredientDessert;
    }

    public function setIngredientDessert(string $ingredientDessert): self
    {
        $this->ingredientDessert = $ingredientDessert;

        return $this;
    }

    public function getCommentaireDessert(): ?string
    {
        return $this->commentaireDessert;
    }

    public function setCommentaireDessert(string $commentaireDessert): self
    {
        $this->commentaireDessert = $commentaireDessert;

        return $this;
    }

    public function getDispoDessert(): ?bool
    {
        return $this->dispoDessert;
    }

    public function setDispoDessert(bool $dispoDessert): self
    {
        $this->dispoDessert = $dispoDessert;

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
            $commandeIndividuelle->setDessertChoisi($this);
        }

        return $this;
    }

    public function removeCommandeIndividuelle(CommandeIndividuelle $commandeIndividuelle): self
    {
        if ($this->commandeIndividuelles->removeElement($commandeIndividuelle)) {
            // set the owning side to null (unless already changed)
            if ($commandeIndividuelle->getDessertChoisi() === $this) {
                $commandeIndividuelle->setDessertChoisi(null);
            }
        }

        return $this;
    }
}
