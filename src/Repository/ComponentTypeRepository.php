<?php

namespace App\Repository;

use App\Entity\ComponentType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class ComponentTypeRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, ComponentType::class);
        $this->entityManager = $entityManager;
    }

    /**
     * Find a component type by name
     */
    public function findByName(string $name): ?ComponentType
    {
        return $this->createQueryBuilder('ct')
            ->andWhere('ct.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $name
     * @return int|null
     */
    public function findComponentIdByName(string $name): ?ComponentType
    {
        return $this->createQueryBuilder('ct')
            ->andWhere('ct.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
