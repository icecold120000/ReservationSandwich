<?php

namespace App\Entity;

use App\Repository\SandwichRepository;
use Doctrine\ORM\Mapping as ORM;

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
     * @ORM\Column(type="string", length=255)
     */
    private ?string $ingredientSandwich;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $commentaireSandwich;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $disponibleSandwich;

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

    public function getDisponibleSandwich(): ?bool
    {
        return $this->disponibleSandwich;
    }

    public function setDisponibleSandwich(bool $disponibleSandwich): self
    {
        $this->disponibleSandwich = $disponibleSandwich;

        return $this;
    }
}
