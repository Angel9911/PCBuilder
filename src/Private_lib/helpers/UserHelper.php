<?php

namespace App\Private_lib\helpers;

use App\Entity\User\User;
use App\Entity\User\UserAccount;
use App\Repository\UserRepository;
use App\Repository\UserRoleRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserHelper
{
    private UserRepository $userRepository;
    private UserRoleRepository $roleRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        UserRepository $userRepository,
        UserRoleRepository $roleRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Get an existing user by email, or create an anonymous one.
     */
    public function getOrCreateAnonymousUser(string $name, string $email): User
    {
        $user = $this->userRepository->findUserByEmail($email);

        if ($user !== null) {
            return $user;
        }

        // Get anonymous role
        $anonymousRole = $this->roleRepository->findRoleByName('anonymous');

        // Create minimal UserAccount for anonymous users
        $userAccount = new UserAccount(
            username: $email,
            password: ''
        );
        $userAccount->setRole($anonymousRole);

        // Create User entity
        $user = new User($userAccount, $name, '');
        $user->setEmail($email);

        // Persist user
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}