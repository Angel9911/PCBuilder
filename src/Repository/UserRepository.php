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
     * Find a user by username
     */
    public function findUserByUsername(string $username): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.username = :username')
            ->setParameter('username', $username)
            ->getQuery()
            ->getOneOrNullResult();
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
     * Find all users with a specific role
     */
    public function findUsersByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.account', 'ua') // Assuming User has a relation to UserAccount
            ->andWhere('ua.role = :role')
            ->setParameter('role', $role)
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if a phone number is already registered
     */
    public function isPhoneNumberTaken(string $phone): bool
    {
        return (bool) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.phone = :phone')
            ->setParameter('phone', $phone)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Save a user (insert/update)
     */
    public function save(User $user, bool $flush = true): void
    {
        $this->entityManager->persist($user);
        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     * Remove a user
     */
    public function remove(User $user, bool $flush = true): void
    {
        $this->entityManager->remove($user);
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