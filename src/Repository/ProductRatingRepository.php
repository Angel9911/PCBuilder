<?php

namespace App\Repository;

use App\Entity\Periphery\Periphery;
use App\Entity\ProductRating;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class ProductRatingRepository extends ServiceEntityRepository
{
    protected EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, ProductRating::class);
        $this->entityManager = $entityManager;
    }

    public function saveProductRating(ProductRating $productRating, bool $flush = true): void
    {
        $this->entityManager->persist($productRating);

        if ($flush) {
            $this->entityManager->flush();
        }
    }
}