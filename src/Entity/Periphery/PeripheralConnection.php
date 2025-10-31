<?php

namespace App\Entity\Periphery;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
#[ORM\Entity(readOnly: true)]
#[ORM\Table(name: 'peripheral_connections')]
class PeripheralConnection
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Periphery::class, inversedBy: "connections")]
    #[ORM\JoinColumn(name: "peripheral_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private Periphery $peripheral;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: PeripheryConnection::class, inversedBy: "peripheralLinks")]
    #[ORM\JoinColumn(name: "connection_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private PeripheryConnection $connection;

    public function __construct()
    {

    }

    public function getPeripheral(): Periphery
    {
        return $this->peripheral;
    }

    public function setPeripheral(Periphery $peripheral): void
    {
        $this->peripheral = $peripheral;
    }

    public function getConnection(): PeripheryConnection
    {
        return $this->connection;
    }

    public function setConnection(PeripheryConnection $connection): void
    {
        $this->connection = $connection;
    }
}