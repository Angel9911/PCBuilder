<?php

namespace App\Repository;

use App\Entity\User\User;
use App\Entity\User\UserRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class UserRoleRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, UserRole::class);
        $this->entityManager = $entityManager;
    }

    public function findRoleByName(string $name): ?UserRole
    {
        return $this->createQueryBuilder('ur')
            ->andWhere('ur.role = :role')
            ->setParameter('role', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }
}