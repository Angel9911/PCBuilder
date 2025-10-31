<?php declare(strict_types=1);


namespace App\Entity;
use App\Entity\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(readOnly: true)]
#[ORM\Table(name: "pc_configurations")]
class CompletedConfiguration
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $totalWattage = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $lowestPrice;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $highestPrice;

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeInterface $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'savedConfigurations')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?User $user;

    #[ORM\ManyToOne(targetEntity: CompletedConfigurationType::class, inversedBy: "configurations")]
    #[ORM\JoinColumn(name: "type_id", referencedColumnName: "id", nullable: false)]
    private ?CompletedConfigurationType $type = null;

    #[ORM\OneToMany(targetEntity: PCConfigComponent::class, mappedBy: 'configuration', cascade: ['persist', 'remove'], fetch: 'LAZY')]
    private Collection $components;

    #[ORM\OneToMany(targetEntity: CompletedConfigurationRating::class, mappedBy: "configuration", cascade: ["remove"])]
    private Collection $ratings;

    public function __construct(User $user = null)
    {
        $this->user = $user;
        $this->components = new ArrayCollection();
        $this->ratings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTotalWattage(): int
    {
        return $this->totalWattage;
    }

    public function setTotalWattage(int $totalWattage): self
    {
        $this->totalWattage = $totalWattage;
        return $this;
    }

    public function getLowestPrice(): int
    {
        return $this->lowestPrice;
    }

    public function setLowestPrice(int $lowestPrice): self
    {
        $this->lowestPrice = $lowestPrice;
        return $this;
    }

    public function getHighestPrice(): int
    {
        return $this->highestPrice;
    }

    public function setHighestPrice(int $highestPrice): void
    {
        $this->highestPrice = $highestPrice;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getComponents(): Collection
    {
        return $this->components;
    }


    // New method to convert the object to an array for debugging purposes
    public function toArray(): array
    {
        $result = [
            'id' => $this->id,
            'name' => $this->name,
            'totalWattage' => $this->totalWattage,
            'components' => []
        ];

        foreach ($this->components as $component) {
            $result['components'][] = [
                'component_id' => $component->getId(),
                'component_name' => $component->getName(),
                'component_type' => $component->getType()->getName(),
                'power_wattage' => $component->getPowerWattage()
            ];
        }

        return $result;
    }
    public function addComponent(PCConfigComponent $component): self
    {
        if(!$this->components->contains($component)) {

            $this->components->add($component);

            $component->setConfiguration($this);
        }
        return $this;
    }

    public function removeComponent(PCConfigComponent $component): self
    {
        if($this->components->contains($component)) {

            $this->components->removeElement($component);

            if($component->getConfiguration() === $this) {

                $component->setConfiguration();
            }
        }
        return $this;
    }

    public function getType(): CompletedConfigurationType
    {
        return $this->type;
    }

    public function setType(CompletedConfigurationType $type): void
    {
        $this->type = $type;
    }

    public function getRatings(): Collection
    {
        return $this->ratings;
    }

    public function setRatings(Collection $ratings): void
    {
        $this->ratings = $ratings;
    }
}
