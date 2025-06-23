<?php

namespace App\Repository;

use App\Entity\ComponentImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class ComponentImageRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, ComponentImage::class);
        $this->entityManager = $entityManager;
    }

    public function getImageUrlByComponentId(int $componentId): string
    {
        $result = $this->createQueryBuilder('i')
            ->leftJoin('i.component', 'ci')
            ->select('i.imageUrl')
            ->andWhere('ci.id = :componentId AND i.isPrimary = :status')
            ->setParameter('componentId', $componentId)
            ->setParameter('status', true)
            ->getQuery()
            ->getSingleScalarResult();

        return $result;
    }
}