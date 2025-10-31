<?php

namespace App\Repository;

use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, User::class);
        $this->entityManager = $entityManager;
    }

    /**
     * Find a user by email
     */
    public function findUserByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Save a user (insert/update)
     */
    public function save(User $user, bool $flush = true): void
    {
        // have to return ID of created user, to use it where it's need.

        $this->entityManager->persist($user);
        if ($flush) {
            $this->entityManager->flush();
        }
    }


    /**
     * Find users who have saved configurations
     */
    public function findUsersWithSavedConfigurations(): array
    {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.savedConfigurations', 'sc')
            ->addSelect('sc')
            ->getQuery()
            ->getResult();
    }
}