<?php declare(strict_types=1);


namespace App\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "user_accounts")]
class UserAccount
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255, unique: true)]
    private string $username;

    #[ORM\Column(type: "string", length: 255)]
    private string $password;

    #[ORM\ManyToOne(targetEntity: UserRole::class)]
    #[ORM\JoinColumn(name: 'role_id', referencedColumnName: 'id')]
    private UserRole $role;

    #[ORM\OneToOne(targetEntity: User::class, mappedBy: "account", cascade: ["persist", "remove"], fetch: "EAGER")]
    private ?User $user = null;

    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }


    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function setRole(UserRole $role): void
    {
        $this->role = $role;
    }
}
