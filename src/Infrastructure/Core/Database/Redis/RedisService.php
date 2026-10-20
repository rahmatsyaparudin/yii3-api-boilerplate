<?php

declare(strict_types=1);

namespace App\Infrastructure\Core\Database\Redis;

final class RedisService
{
    private ?\Redis $redis = null;

    public function __construct(
        private string $host = '127.0.0.1',
        private int $port = 6379,
        private int $database = 0,
        private ?string $password = null,
        private bool $enabled = true
    ) {
        if (!$this->enabled || !\extension_loaded('redis')) {
            $this->enabled = false;
            return;
        }

        try {
            $redis = new \Redis();
            $redis->connect($this->host, $this->port, 1.0);

            if ($this->password !== null && $this->password !== '') {
                $redis->auth($this->password);
            }

            if ($this->database > 0) {
                $redis->select($this->database);
            }

            $this->redis = $redis;
        } catch (\Throwable) {
            $this->enabled = false;
            $this->redis = null;
        }
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getClient(): ?\Redis
    {
        return $this->redis;
    }
}
