<?php

namespace App\Entity\Forum;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'forum_sections')]
class ForumSection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 255)]
    private string $title;

/*    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $createdAt;*/

    #[ORM\OneToMany(targetEntity: ForumSubsection::class, mappedBy: "section", cascade: ["persist", "remove"])]
    private Collection $subsections;

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

    /*public function getDescription(): ?string
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
    }*/

    public function getSubsections(): Collection
    {
        return $this->subsections;
    }

    public function setSubsections(Collection $subsections): void
    {
        $this->subsections = $subsections;
    }

}