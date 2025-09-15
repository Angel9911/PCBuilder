<?php declare(strict_types=1);

namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "user_profiles")]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255)]
    private string $fullName;

    #[ORM\Column(type: "string", length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\OneToOne(targetEntity: UserAccount::class, inversedBy: "profile", cascade: ["persist"], fetch: "EAGER")]
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
}
