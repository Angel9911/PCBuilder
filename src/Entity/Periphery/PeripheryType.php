<?php

namespace App\Entity\Periphery;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: "periphery_types")]
class PeripheryType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 50, unique: true)]
    private string $name;

    #[ORM\OneToMany(targetEntity: Periphery::class, mappedBy: "type")]
    private Collection $peripherals;

    public function __construct()
    {
        $this->peripherals = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPeripherals(): Collection
    {
        return $this->peripherals;
    }

    public function setPeripherals(Collection $peripherals): void
    {
        $this->peripherals = $peripherals;
    }
}