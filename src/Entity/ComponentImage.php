<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(readOnly: true)]
#[ORM\Table(name: 'component_images')]
class ComponentImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Component::class, inversedBy: 'images')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Component $component;

    #[ORM\Column(name: "image_url", type: "string", length: 255)]
    private string $imageUrl;

    #[ORM\Column(type: 'boolean')]
    private bool $isPrimary = false;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
    }

    public function getComponent(): Component
    {
        return $this->component;
    }

    public function setComponent(Component $component): void
    {
        $this->component = $component;
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
}