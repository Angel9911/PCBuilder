<?php

namespace App\Service\Impl;

use App\Repository\ComponentRepository;
use App\Repository\UserRepository;
use App\Service\UserService;

class UserServiceImpl implements UserService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository){

        $this->userRepository = $userRepository;
    }

    public function findUserIdByEmail(string $email): ?int
    {
        $this->userRepository->findUserByEmail($email);
    }
}