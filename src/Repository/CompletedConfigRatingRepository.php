<?php

namespace App\Repository;

use App\Entity\CompletedConfiguration;
use App\Entity\CompletedConfigurationRating;
use App\Entity\ProductRating;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class CompletedConfigRatingRepository extends ServiceEntityRepository
{
    protected EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, CompletedConfigurationRating::class);

        $this->entityManager = $entityManager;
    }

    public function savePcConfigRating(CompletedConfigurationRating $completedConfigurationRating, bool $flush = true): void
    {
        $this->entityManager->persist($completedConfigurationRating);

        if ($flush) {

            $this->entityManager->flush();
        }
    }
}