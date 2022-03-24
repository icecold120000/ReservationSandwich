<?php

namespace App\Entity;

use App\Repository\LieuLivraisonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LieuLivraisonRepository::class)
 */
class LieuLivraison
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
    private $libelleLieu;

    /**
     * @ORM\OneToMany(targetEntity=CommandeGroupe::class, mappedBy="lieuLivraison")
     */
    private $CommandeGroupe;

    /**
     * @ORM\Column(type="boolean")
     */
    private $estActive;

    public function __construct()
    {
        $this->CommandeGroupe = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelleLieu(): ?string
    {
        return $this->libelleLieu;
    }

    public function setLibelleLieu(string $libelleLieu): self
    {
        $this->libelleLieu = $libelleLieu;

        return $this;
    }

    /**
     * @return Collection|CommandeGroupe[]
     */
    public function getCommandeGroupe(): Collection
    {
        return $this->CommandeGroupe;
    }

    public function addCommandeGroupe(CommandeGroupe $commandeGroupe): self
    {
        if (!$this->CommandeGroupe->contains($commandeGroupe)) {
            $this->CommandeGroupe[] = $commandeGroupe;
            $commandeGroupe->setLieuLivraison($this);
        }

        return $this;
    }

    public function removeCommandeGroupe(CommandeGroupe $commandeGroupe): self
    {
        if ($this->CommandeGroupe->removeElement($commandeGroupe)) {
            // set the owning side to null (unless already changed)
            if ($commandeGroupe->getLieuLivraison() === $this) {
                $commandeGroupe->setLieuLivraison(null);
            }
        }

        return $this;
    }

    public function getEstActive(): ?bool
    {
        return $this->estActive;
    }

    public function setEstActive(bool $estActive): self
    {
        $this->estActive = $estActive;

        return $this;
    }
}
