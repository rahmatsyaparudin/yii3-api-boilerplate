<?php

declare(strict_types=1);

namespace App\Migration;

// Vendor Layer
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M20240101000000CreateAuditLogsTable implements RevertibleMigrationInterface
{
    private const TABLE_NAME = 'audit_logs';

    public function up(MigrationBuilder $b): void
    {
        $cb = $b->columnBuilder();

        $b->createTable(self::TABLE_NAME, [
            'id'         => $cb::primaryKey(),
            'table_name' => $cb::string(255)->notNull(),
            'record_id'  => $cb::integer()->notNull(),
            'action'     => $cb::string(20)->notNull(),
            'old_values' => $cb::json(),
            'new_values' => $cb::json(),
            'user_id'    => $cb::integer(),
            'user_name'  => $cb::string(255),
            'ip_address' => $cb::string(45),
            'user_agent' => $cb::text(),
            'created_at' => $cb::timestamp(),
        ]);

        $b->createIndex(self::TABLE_NAME, 'idx_audit_table_record', ['table_name', 'record_id']);
        $b->createIndex(self::TABLE_NAME, 'idx_audit_user', ['user_id']);
        $b->createIndex(self::TABLE_NAME, 'idx_audit_created', ['created_at']);
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable(self::TABLE_NAME);
    }
}
