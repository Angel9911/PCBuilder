<?php

namespace App\Entity\Periphery;
use App\Entity\Brand;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(readOnly: true)]
#[ORM\Table(name: "peripherals")]
class Periphery
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255)]
    private string $name;

    #[ORM\Column(type: "string", length: 255)]
    private string $model;

    #[ORM\ManyToOne(targetEntity: PeripheryType::class)]
    #[ORM\JoinColumn(name: "type_id", referencedColumnName: "id", nullable: false)]
    private PeripheryType $type;

    #[ORM\ManyToOne(targetEntity: Brand::class)]
    #[ORM\JoinColumn(name: "brand_id", referencedColumnName: "id", nullable: true, onDelete: "SET NULL")]
    private ?Brand $brand = null;

/*    #[ORM\ManyToOne(targetEntity: PeripheryConnection::class)]
    #[ORM\JoinColumn(name: "connection_id", referencedColumnName: "id", nullable: true, onDelete: "SET NULL")]
    private ?PeripheryConnection $connection = null;*/

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: "image_url", type: "text", nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(name: "slugify_name", type: "string", length: 255)]
    private string $slugifyName;

    #[ORM\OneToMany(targetEntity: PeripheryImage::class, mappedBy: "peripheral")]
    private Collection $images;

    #[ORM\OneToMany(targetEntity: PeripheryConnection::class, mappedBy: "peripheral")]
    private Collection $connections;

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

    public function getModel(): string
    {
        return $this->model;
    }

    public function setModel(string $model): void
    {
        $this->model = $model;
    }

    public function getType(): PeripheryType
    {
        return $this->type;
    }

    public function setType(PeripheryType $type): void
    {
        $this->type = $type;
    }

    public function getBrand(): ?Brand
    {
        return $this->brand;
    }

    public function setBrand(?Brand $brand): void
    {
        $this->brand = $brand;
    }

    public function getConnection(): ?PeripheryConnection
    {
        return $this->connection;
    }

    public function setConnection(?PeripheryConnection $connection): void
    {
        $this->connection = $connection;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): void
    {
        $this->imageUrl = $imageUrl;
    }

    public function getSlugifyName(): string
    {
        return $this->slugifyName;
    }

    public function setSlugifyName(string $slugifyName): void
    {
        $this->slugifyName = $slugifyName;
    }

    public function getImages(): Collection
    {
        return $this->images;
    }

    public function setImages(Collection $images): void
    {
        $this->images = $images;
    }

    public function getConnections(): Collection
    {
        return $this->connections;
    }

    public function setConnections(Collection $connections): void
    {
        $this->connections = $connections;
    }
}