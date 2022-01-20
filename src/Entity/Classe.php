<?php

namespace App\Entity;

use App\Repository\ClasseRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ClasseRepository::class)
 */
class Classe
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
    private ?string $libelleClasse;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $codeClasse;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelleClasse(): ?string
    {
        return $this->libelleClasse;
    }

    public function setLibelleClasse(string $libelleClasse): self
    {
        $this->libelleClasse = $libelleClasse;

        return $this;
    }

    public function getCodeClasse(): ?string
    {
        return $this->codeClasse;
    }

    public function setCodeClasse(string $codeClasse): self
    {
        $this->codeClasse = $codeClasse;

        return $this;
    }
}
