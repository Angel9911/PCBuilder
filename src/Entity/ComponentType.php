<?php declare(strict_types=1);

namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: "component_types")]
class ComponentType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 50, unique: true)]
    #[Groups(["component_type_read"])]
    private string $name;

    #[ORM\OneToMany(targetEntity: Component::class, mappedBy: "type", cascade: ["persist"], fetch: "LAZY")]
    #[Groups(["component_type_read"])]
    private Collection $components;

    public function __construct()
    {
        //$this->name = $name;
        $this->components = new ArrayCollection();
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

    public function getComponents(): Collection
    {
        return $this->components;
    }

    public function setComponents(Collection $components): void
    {
        $this->components = $components;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'components' => array_map(function ($component) {
                return $component->toArray(); // Call the 'toArray()' method of Component to get the full component details
            }, $this->getComponents()->toArray()), // Convert Collection to array
        ];
    }
}
