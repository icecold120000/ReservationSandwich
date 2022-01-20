<?php

namespace App\Entity;

use App\Repository\BoissonRepository;
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
}
