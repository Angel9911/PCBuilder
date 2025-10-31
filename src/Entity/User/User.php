<?php declare(strict_types=1);

namespace App\Entity\User;
use App\Entity\CompletedConfiguration;
use App\Entity\CompletedConfigurationRating;
use App\Entity\ProductRating;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "users")]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(name: 'first_name', type: "string", length: 255)]
    private string $firstName;

    #[ORM\Column(name: 'last_name', type: "string", length: 255)]
    private string $lastName;

    #[ORM\Column(name: 'email', type: "string", length: 255)]
    private string $email;
    #[ORM\Column(type: "string", length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\OneToOne(targetEntity: UserAccount::class, inversedBy: "user", cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(name: 'user_account_id', nullable: false)]
    private UserAccount $account;

    #[ORM\OneToMany(targetEntity: CompletedConfiguration::class, mappedBy: "user", cascade: ["remove"], fetch: "LAZY")]
    private Collection $savedConfigurations;

    #[ORM\OneToMany(targetEntity: ProductRating::class, mappedBy: "user", cascade: ["remove"])]
    private Collection $ratings;

    #[ORM\OneToMany(targetEntity: CompletedConfigurationRating::class, mappedBy: "user", cascade: ["remove"])]
    private Collection $configurationRatings;

    public function __construct(UserAccount $account, string $firstName, string $lastName)
    {
        $this->account = $account;
        $this->firstName = $firstName;
        $this->lastName = $lastName;

        $this->savedConfigurations = new ArrayCollection();

        $this->ratings = new ArrayCollection();
        $this->configurationRatings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }


    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getSavedConfigurations(): Collection
    {
        return $this->savedConfigurations;
    }

    public function setSavedConfigurations(Collection $savedConfigurations): void
    {
        $this->savedConfigurations = $savedConfigurations;
    }

    public function getAccount(): UserAccount
    {
        return $this->account;
    }

    public function setAccount(UserAccount $account): void
    {
        $this->account = $account;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    public function getRatings(): Collection
    {
        return $this->ratings;
    }

    public function setRatings(Collection $ratings): void
    {
        $this->ratings = $ratings;
    }

    public function getConfigurationRatings(): Collection
    {
        return $this->configurationRatings;
    }

    public function setConfigurationRatings(Collection $configurationRatings): void
    {
        $this->configurationRatings = $configurationRatings;
    }

}
