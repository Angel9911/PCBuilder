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

   /* public function findProductRatingByProductIdAndType(int $productId, string $productType): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id) as total_ratings', 'AVG(r.rating) as avg_rating')
            ->where('r.product_id = :productId')
            ->andWhere('r.product_type = :productType')
            ->setParameter('productId', $productId)
            ->setParameter('productType', $productType);

        $result = $qb->getQuery()->getSingleResult();

        return [
            'average' => $result['avg_rating'] !== null ? round((float)$result['avg_rating'], 1) : 0.0,
            'count' => (int)$result['total_ratings'],
        ];
    }*/
}