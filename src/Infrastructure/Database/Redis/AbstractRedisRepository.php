<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Redis;

abstract class AbstractRedisRepository
{
    protected RedisService $redisService;

    public function __construct(RedisService $redisService)
    {
        $this->redisService = $redisService;
    }

    // Redis biasanya menggunakan prefix key, bukan nama collection
    abstract protected function getKeyPrefix(): string;

    protected function createKey(string $id): string
    {
        return $this->getKeyPrefix() . ':' . $id;
    }
}