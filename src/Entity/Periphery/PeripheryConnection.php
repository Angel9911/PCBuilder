<?php

namespace App\Entity\Periphery;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'peripherals_connection')]
class PeripheryConnection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 50, unique: true)]
    private string $name;

    #[ORM\OneToMany(targetEntity: PeripheralConnection::class, mappedBy: "connection")]
    private Collection $peripheralLinks;

    public function __construct()
    {
        $this->peripheralLinks = new ArrayCollection();
    }
}