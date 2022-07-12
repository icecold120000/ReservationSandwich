<?php

namespace App\Entity;

use App\Repository\LimitationCommandeRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LimitationCommandeRepository::class)
 */
class LimitationCommande
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
    private ?string $libelleLimite;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $isActive;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $nbLimite;

    /**
     * @ORM\Column(type="time", nullable=true)
     */
    private ?DateTimeInterface $heureLimite;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelleLimite(): ?string
    {
        return $this->libelleLimite;
    }

    public function setLibelleLimite(?string $libelleLimite): self
    {
        $this->libelleLimite = $libelleLimite;

        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(?bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getNbLimite(): ?int
    {
        return $this->nbLimite;
    }

    public function setNbLimite(?int $nbLimite): self
    {
        $this->nbLimite = $nbLimite;

        return $this;
    }

    public function getHeureLimite(): ?DateTimeInterface
    {
        return $this->heureLimite;
    }

    public function setHeureLimite(?DateTimeInterface $heureLimite): self
    {
        $this->heureLimite = $heureLimite;

        return $this;
    }
}
