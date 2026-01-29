<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Redis;

use Redis;

final class RedisService
{
    private Redis $redis;

    public function __construct(string $host = '127.0.0.1', int $port = 6379)
    {
        $this->redis = new Redis();
        $this->redis->connect($host, $port);
    }

    public function getClient(): Redis
    {
        return $this->redis;
    }
}