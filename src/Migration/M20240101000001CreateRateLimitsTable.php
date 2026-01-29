<?php

declare(strict_types=1);

namespace App\Migration;

// Vendor Layer
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M20240101000001CreateRateLimitsTable implements RevertibleMigrationInterface
{
    private const TABLE_NAME = 'rate_limits';
    private const INDEX_KEY_CREATED = 'idx_' . self::TABLE_NAME . '_key_created';
    private const INDEX_CREATED = 'idx_' . self::TABLE_NAME . '_created';

    public function up(MigrationBuilder $b): void
    {
        $cb = $b->columnBuilder();

        $b->createTable(self::TABLE_NAME, [
            'id'         => $cb::primaryKey(),
            'key'        => $cb::string(255)->notNull(),
            'created_at' => $cb::timestamp(),
        ]);

        $b->createIndex('rate_limits', 'idx_rate_limits_key_created', ['key', 'created_at']);
        $b->createIndex('rate_limits', 'idx_rate_limits_created', ['created_at']);
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable(self::TABLE_NAME);
    }
}
