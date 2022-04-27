<?php

namespace App\Entity;

use App\Repository\DesactivationCommandeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DesactivationCommandeRepository::class)
 */
class DesactivationCommande
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $isDeactivated;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIsDeactivated(): ?bool
    {
        return $this->isDeactivated;
    }

    public function setIsDeactivated(bool $isDeactivated): self
    {
        $this->isDeactivated = $isDeactivated;

        return $this;
    }
}
