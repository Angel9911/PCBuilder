<?php

namespace App\Entity\Forum;

use App\Entity\User\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'forum_comments')]
class ForumComment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "text")]
    private string $content;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $createdAt;

    #[ORM\ManyToOne(targetEntity: ForumTopic::class, inversedBy: "comments")]
    #[ORM\JoinColumn(name: "topic_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private ForumTopic $topic;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: ForumComment::class)]
    #[ORM\JoinColumn(name: "parent_id", referencedColumnName: "id", nullable: true, onDelete: "CASCADE")]
    private ?ForumComment $parent = null;



    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getTopic(): ForumTopic
    {
        return $this->topic;
    }

    public function setTopic(ForumTopic $topic): void
    {
        $this->topic = $topic;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getParent(): ?ForumComment
    {
        return $this->parent;
    }

    public function setParent(?ForumComment $parent): void
    {
        $this->parent = $parent;
    }


}