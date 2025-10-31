<?php

namespace App\Service;

interface UserService
{
    public function findUserIdByEmail(string $email): ?int;
}