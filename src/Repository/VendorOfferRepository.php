<?php

namespace App\Repository;

use App\Entity\Component;
use App\Entity\VendorOffers;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class VendorOfferRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;

    /**
     * @param ManagerRegistry $registry
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, VendorOffers::class);
        $this->entityManager = $entityManager;
    }

    /**
     * @param int $componentId
     * @return array
     */
    public function findOffersByComponentId(int $componentId): array
    {
        return $this->createQueryBuilder('vo')
            ->join('vo.vendor', 'v')  // Joining Vendor entity (correct)
            ->join('vo.component', 'co')  // Joining Component entity
            ->addSelect('v', 'co') // Select Vendor and Component
            ->where('co.id = :componentId')  // Using correct alias 'co'
            ->setParameter('componentId', $componentId)
            ->getQuery()
            ->getResult();
    }

    public function findAllOffers()
    {

    }

}