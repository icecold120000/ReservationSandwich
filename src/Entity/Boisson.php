<?php

namespace App\Entity;

use App\Repository\BoissonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=BoissonRepository::class)
 */
class Boisson
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
    private ?string $nomBoisson;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $imageBoisson;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $dispoBoisson;

    /**
     * @ORM\OneToMany(targetEntity=CommandeIndividuelle::class, mappedBy="boissonChoisie", orphanRemoval=true)
     */
    private $commandeIndividuelles;

    /**
     * @ORM\OneToMany(targetEntity=CommandeGroupe::class, mappedBy="boissonChoisie", orphanRemoval=true)
     */
    private $commandeGroupes;

    public function __construct()
    {
        $this->commandeIndividuelles = new ArrayCollection();
        $this->commandeGroupes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomBoisson(): ?string
    {
        return $this->nomBoisson;
    }

    public function setNomBoisson(string $nomBoisson): self
    {
        $this->nomBoisson = $nomBoisson;

        return $this;
    }

    public function getImageBoisson(): ?string
    {
        return $this->imageBoisson;
    }

    public function setImageBoisson(string $imageBoisson): self
    {
        $this->imageBoisson = $imageBoisson;

        return $this;
    }

    public function getDispoBoisson(): ?bool
    {
        return $this->dispoBoisson;
    }

    public function setDispoBoisson(bool $dispoBoisson): self
    {
        $this->dispoBoisson = $dispoBoisson;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getCommandeIndividuelles(): Collection
    {
        return $this->commandeIndividuelles;
    }

    public function addCommandeIndividuelle(CommandeIndividuelle $commandeIndividuelle): self
    {
        if (!$this->commandeIndividuelles->contains($commandeIndividuelle)) {
            $this->commandeIndividuelles[] = $commandeIndividuelle;
            $commandeIndividuelle->setBoissonChoisie($this);
        }

        return $this;
    }

    public function removeCommandeIndividuelle(CommandeIndividuelle $commandeIndividuelle): self
    {
        if ($this->commandeIndividuelles->removeElement($commandeIndividuelle)) {
            // set the owning side to null (unless already changed)
            if ($commandeIndividuelle->getBoissonChoisie() === $this) {
                $commandeIndividuelle->setBoissonChoisie(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection
     */
    public function getCommandeGroupes(): Collection
    {
        return $this->commandeGroupes;
    }

    public function addCommandeGroupe(CommandeGroupe $commandeGroupe): self
    {
        if (!$this->commandeGroupes->contains($commandeGroupe)) {
            $this->commandeGroupes[] = $commandeGroupe;
            $commandeGroupe->setBoissonChoisie($this);
        }

        return $this;
    }

    public function removeCommandeGroupe(CommandeGroupe $commandeGroupe): self
    {
        if ($this->commandeGroupes->removeElement($commandeGroupe)) {
            // set the owning side to null (unless already changed)
            if ($commandeGroupe->getBoissonChoisie() === $this) {
                $commandeGroupe->setBoissonChoisie(null);
            }
        }

        return $this;
    }
}
