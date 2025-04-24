<?php

namespace App\Entity\Forum;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'forum_subsections')]
class ForumSubsection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 255)]
    private string $title;

    /*#[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $createdAt;*/

    #[ORM\ManyToOne(targetEntity: ForumSection::class, inversedBy: "subsections")]
    #[ORM\JoinColumn(name: "section_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private ForumSection $section;

    #[ORM\OneToMany(targetEntity: ForumTopic::class, mappedBy: "subsection", cascade: ["persist", "remove"])]
    private Collection $topics;

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

    public function getSection(): ForumSection
    {
        return $this->section;
    }

    public function setSection(ForumSection $section): void
    {
        $this->section = $section;
    }

    public function getTopics(): Collection
    {
        return $this->topics;
    }

    public function setTopics(Collection $topics): void
    {
        $this->topics = $topics;
    }


}