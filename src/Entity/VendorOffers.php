<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'vendor_offers')]
class VendorOffers
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;
    #[ORM\Column(type: "string", length: 255)]
    private string $productUrl;

    #[ORM\ManyToOne(targetEntity: Component::class, fetch: "LAZY")]
    #[ORM\JoinColumn(name: "component_id", referencedColumnName: "id")]
    private Component $component;

    #[ORM\ManyToOne(targetEntity: VendorEntity::class, fetch: "LAZY")]
    #[ORM\JoinColumn(name: "vendor_id", referencedColumnName: "id", onDelete: "CASCADE")]
    private VendorEntity $vendor;

    /**
     * @param int|null $id
     * @param int $componentId
     * @param string $productUrl
     * @param Vendor $vendor
     */
    public function __construct(?int $id, Component $component, string $productUrl, VendorEntity $vendor)
    {
        $this->id = $id;
        $this->component = $component;
        $this->productUrl = $productUrl;
        $this->vendor = $vendor;
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getComponent(): Component
    {
        return $this->component;
    }

    public function setComponent(Component $component): void
    {
        $this->component = $component;
    }

    public function getProductUrl(): string
    {
        return $this->productUrl;
    }

    public function setProductUrl(string $productUrl): void
    {
        $this->productUrl = $productUrl;
    }

    public function getVendorEntity(): VendorEntity
    {
        return $this->vendor;
    }

    public function setVendorEntity(VendorEntity $vendor): void
    {
        $this->vendor = $vendor;
    }



}