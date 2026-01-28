<?php

declare(strict_types=1);

namespace App\Migration;

// Vendor Layer
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M20240101000001CreateRateLimits implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $cb = $b->columnBuilder();

        $b->createTable('rate_limits', [
            'id'         => $cb::primaryKey(),
            'key'        => $cb::string(255)->notNull(),
            'created_at' => $cb::timestamp(),
        ]);

        $b->createIndex('rate_limits', 'idx_rate_limits_key_created', ['key', 'created_at']);
        $b->createIndex('rate_limits', 'idx_rate_limits_created', ['created_at']);
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('rate_limits');
    }
}
