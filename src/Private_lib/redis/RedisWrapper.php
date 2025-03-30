<?php

namespace App\Private_lib\redis;

interface RedisWrapper
{
    public function set(string $key, mixed $value, int $ttl = 3600): void;
    public function get(string $key): mixed;
    public function getTTL(string $key): int;
    public function isKeyExist(string $key): bool;
    public function delete(string $key): void;
}