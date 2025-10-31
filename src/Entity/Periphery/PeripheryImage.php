<?php

namespace App\Entity\Periphery;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(readOnly: true)]
#[ORM\Table(name: 'periphery_images')]
class PeripheryImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Periphery::class, inversedBy: "images")]
    #[ORM\JoinColumn(name: "peripheral_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private Periphery $peripheral;

    #[ORM\Column(name: "image_url", type: "text")]
    private string $imageUrl;

    #[ORM\Column(type: "boolean", options: ["default" => false])]
    private bool $isPrimary = false;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getPeripheral(): Periphery
    {
        return $this->peripheral;
    }

    public function setPeripheral(Periphery $peripheral): void
    {
        $this->peripheral = $peripheral;
    }

    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(string $imageUrl): void
    {
        $this->imageUrl = $imageUrl;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function setIsPrimary(bool $isPrimary): void
    {
        $this->isPrimary = $isPrimary;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}