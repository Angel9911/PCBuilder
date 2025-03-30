<?php

namespace App\Entity;


use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'vendors')]
class VendorEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255)]
    private string $logo;

    #[ORM\Column(type: 'string', length: 255)]
    private string $domainUrl;

    /**
     * @param int|null $id
     * @param string $name
     * @param string $logo
     * @param string $domainUrl
     */
    public function __construct(?int $id, string $name, string $logo, string $domainUrl)
    {
        $this->id = $id;
        $this->name = $name;
        $this->logo = $logo;
        $this->domainUrl = $domainUrl;
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

    public function getLogo(): string
    {
        return $this->logo;
    }

    public function setLogo(string $logo): void
    {
        $this->logo = $logo;
    }

    public function getDomainUrl(): string
    {
        return $this->domainUrl;
    }

    public function setDomainUrl(string $domainUrl): void
    {
        $this->domainUrl = $domainUrl;
    }
}