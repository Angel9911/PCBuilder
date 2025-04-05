<?php declare(strict_types=1);


namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: "components")]
class Component
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;
    #[ORM\Column(type: "string", length: 255)]
    #[Groups(["component_read"])]
    private string $name;

    #[ORM\Column(type: "string", length: 255)]
    #[Groups(["component_read"])]
    private string $model;

    #[ORM\ManyToOne(targetEntity: ComponentType::class, fetch: "EAGER", inversedBy: 'components')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["component_read"])]
    private ComponentType $type;

    #[ORM\Column(type: "integer", options: ["default" => 0])]
    #[Groups(["component_read"])]
    private int $powerWattage = 0;

    #[ORM\OneToMany(targetEntity: PCConfigComponent::class, mappedBy: "component")]
    private Collection $pcConfigComponents;

    public function __construct(string $name, string $model, ComponentType $type, int $powerWattage = 0)
    {
        $this->name = $name;
        $this->model = $model;
        $this->type = $type;
        $this->powerWattage = $powerWattage;
        $this->pcConfigComponents = new ArrayCollection();
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

    public function getModel(): string
    {
        return $this->model;
    }

    public function setModel(string $model): void
    {
        $this->model = $model;
    }

    public function getType(): ComponentType
    {
        return $this->type;
    }

    public function setType(ComponentType $type): void
    {
        $this->type = $type;
    }

    public function getPowerWattage(): int
    {
        return $this->powerWattage;
    }

    public function setPowerWattage(int $powerWattage): void
    {
        $this->powerWattage = $powerWattage;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'model' => $this->getModel(),
            'type' => $this->getType()->getName(), // Assuming 'name' is the key you want from the ComponentType
            'powerWattage' => $this->getPowerWattage(),
        ];
    }

    public function getPcConfigComponent(): Collection
    {
        return $this->pcConfigComponents;
    }
}

