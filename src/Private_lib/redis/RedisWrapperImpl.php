<?php

namespace App\Private_lib\redis;

use Predis\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class RedisWrapperImpl implements RedisWrapper
{

    private Client $redis;

    public function __construct(ParameterBagInterface $params)
    {

        $redisPort = $params->get('redis_port');

        $redisHost = $params->get('redis_host');

        $redisPassword = $params->get('redis_password');

        $this->redis = new Client([
            'scheme' => 'tcp',
            'host'   => $redisHost,
            'port'   => $redisPort,
            'password' => $redisPassword,
            'aggregate' => 'single'
        ]);
    }

    public function set(string $key, mixed $value, int $ttl = 3600): void
    {
        $this->redis->set($key, serialize($value)); // Store serialized value
        $this->redis->expire($key, $ttl); // Set expiration time
    }

    public function get(string $key): mixed
    {
        $value = $this->redis->get($key);
        return $value ? unserialize($value) : null;
    }

    public function getTTL(string $key): int
    {
        return $this->redis->ttl($key);
    }

    public function isKeyExist(string $key): bool
    {
        return $this->redis->exists($key) > 0;
    }

    public function delete(string $key): void
    {
        $this->redis->del([$key]);
    }
}