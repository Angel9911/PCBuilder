<?php

namespace App\Repository;

use App\Entity\User\User;
use App\Entity\User\UserAccount;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class UserAccountRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, UserAccount::class);

        $this->entityManager = $entityManager;
    }

    public function createUserAccount(User $user, bool $flush = true)
    {
        // have to return ID of created account, to use it for User object.
        $this->entityManager->persist($user);
        if ($flush) {
            $this->entityManager->flush();
        }
    }
}