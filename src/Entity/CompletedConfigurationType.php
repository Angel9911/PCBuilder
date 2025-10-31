<?php declare(strict_types=1);

namespace App\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
#[ORM\Table(name: "pc_configuration_types")]
class CompletedConfigurationType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 50, unique: true)]
    private string $type; // e.g. "user", "system", "creator"

    #[ORM\Column(type: "string", length: 100)]
    private string $label; // Human readable, e.g. "User Generated", "System", "Content Creator"

    #[ORM\OneToMany(targetEntity: CompletedConfiguration::class, mappedBy: "type")]
    private Collection $configurations;

    public function __construct()
    {
        $this->configurations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getConfigurations(): Collection
    {
        return $this->configurations;
    }

    public function setConfigurations(Collection $configurations): void
    {
        $this->configurations = $configurations;
    }

}