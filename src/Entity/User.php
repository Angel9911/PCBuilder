<?php declare(strict_types=1);

namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
#[ORM\Entity]
#[ORM\Table(name: "user_profiles")]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(name: 'first_name', type: "string", length: 255)]
    private string $firstName;

    #[ORM\Column(name: 'last_name ', type: "string", length: 255)]
    private string $lastName;

    #[ORM\Column(type: "string", length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\OneToOne(targetEntity: UserAccount::class, inversedBy: "user", cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(nullable: false)]
    private UserAccount $account;

    #[ORM\OneToMany(targetEntity: CompletedConfiguration::class, mappedBy: "user", cascade: ["remove"], fetch: "LAZY")]
    private Collection $savedConfigurations;

    public function __construct(UserAccount $account, string $fullName)
    {
        $this->account = $account;
        $this->fullName = $fullName;
        $this->savedConfigurations = new ArrayCollection();
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

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }

}
