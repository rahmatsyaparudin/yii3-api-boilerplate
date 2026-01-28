<?php

declare(strict_types=1);

namespace App\Infrastructure\RateLimit;

// Infrastructure Layer
use Yiisoft\Cache\CacheInterface;

// Vendor Layer
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

final class DatabaseRateLimiter
{
    public function __construct(
        private ConnectionInterface $db,
        private CacheInterface $cache
    ) {
    }

    public function isAllowed(string $key, int $limit, int $window): bool
    {
        $now         = \time();
        $windowStart = $now - $window;

        // Clean old entries
        $this->cleanup($key, $windowStart);

        // Count current requests
        $count = (new Query($this->db))
            ->from('rate_limits')
            ->where(['key' => $key])
            ->andWhere(['>=', 'created_at', \date('Y-m-d H:i:s', $windowStart)])
            ->count();

        return $count < $limit;
    }

    public function hit(string $key): void
    {
        (new Query($this->db))->createCommand()->insert('rate_limits', [
            'key'        => $key,
            'created_at' => \date('Y-m-d H:i:s'),
        ])->execute();
    }

    public function getRemaining(string $key, int $limit, int $window): int
    {
        $now         = \time();
        $windowStart = $now - $window;

        $count = (new Query($this->db))
            ->from('rate_limits')
            ->where(['key' => $key])
            ->andWhere(['>=', 'created_at', \date('Y-m-d H:i:s', $windowStart)])
            ->count();

        return \max(0, $limit - $count);
    }

    public function getResetTime(string $key, int $window): int
    {
        $oldest = (new Query($this->db))
            ->from('rate_limits')
            ->where(['key' => $key])
            ->min('created_at');

        if ($oldest) {
            return \strtotime($oldest) + $window;
        }

        return \time() + $window;
    }

    private function cleanup(string $key, int $windowStart): void
    {
        (new Query($this->db))->createCommand()->delete('rate_limits', [
            'key' => $key,
            '<', 'created_at', \date('Y-m-d H:i:s', $windowStart),
        ])->execute();
    }
}
