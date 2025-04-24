<?php

namespace App\Entity\Forum;

use App\Entity\CompletedConfiguration;
use App\Entity\User\User;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'forum_topics')]
class ForumTopic
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 255)]
    private string $title;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $createdAt;

    #[ORM\ManyToOne(targetEntity: ForumSubsection::class, inversedBy: "topics")]
    #[ORM\JoinColumn(name: "subsection_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private ForumSubsection $subsection;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: CompletedConfiguration::class)]
    #[ORM\JoinColumn(name: "pc_config_id", referencedColumnName: "id", nullable: true, onDelete: "SET NULL")]
    private ?CompletedConfiguration $linkedConfiguration = null;

    #[ORM\OneToMany(targetEntity: ForumComment::class, mappedBy: "topic", cascade: ["persist", "remove"])]
    private Collection $comments;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getSubsection(): ForumSubsection
    {
        return $this->subsection;
    }

    public function setSubsection(ForumSubsection $subsection): void
    {
        $this->subsection = $subsection;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getLinkedConfiguration(): ?CompletedConfiguration
    {
        return $this->linkedConfiguration;
    }

    public function setLinkedConfiguration(?CompletedConfiguration $linkedConfiguration): void
    {
        $this->linkedConfiguration = $linkedConfiguration;
    }

    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function setComments(Collection $comments): void
    {
        $this->comments = $comments;
    }
}