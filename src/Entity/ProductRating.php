<?php

namespace App\Entity;
use App\Entity\User\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(readOnly: true)]
#[ORM\Table(name: "product_ratings")]
class ProductRating
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private User $user;

    #[ORM\Column(name: "product_id", type: "integer")]
    private int $productId;

    #[ORM\Column(name: "product_type", type: "string", length: 20)]
    private string $productType; // "component" or "peripheral"

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $rating = 0;
    #[ORM\Column(type: "text")]
    private string $review;

    #[ORM\Column(name: "created_at", type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(name: "updated_at", type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeInterface $updatedAt;

    /**
     * @param int $productId
     * @param string $productType
     * @param int $rating
     */
    public function __construct(int $productId, string $productType, int $rating)
    {
        $this->productId = $productId;
        $this->productType = $productType;
        $this->rating = $rating;

        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function setProductId(int $productId): void
    {
        $this->productId = $productId;
    }

    public function getProductType(): string
    {
        return $this->productType;
    }

    public function setProductType(string $productType): void
    {
        $this->productType = $productType;
    }

    public function getRating(): int
    {
        return $this->rating;
    }

    public function setRating(int $rating): self
    {
        if($rating < 1 || $rating > 5) {

            throw new \InvalidArgumentException("Rating must be between 1 and 5");
        }
        $this->rating = $rating;

        return $this;
    }

    public function getReview(): string
    {
        return $this->review;
    }

    public function setReview(string $review): void
    {
        $this->review = $review;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

}