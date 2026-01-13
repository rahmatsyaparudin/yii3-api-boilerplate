<?php

declare(strict_types=1);

namespace App\Migration;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M20240101000000CreateAuditLogs implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $cb = $b->columnBuilder();

        $b->createTable('audit_logs', [
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

        $b->createIndex('audit_logs', 'idx_audit_table_record', ['table_name', 'record_id']);
        $b->createIndex('audit_logs', 'idx_audit_user', ['user_id']);
        $b->createIndex('audit_logs', 'idx_audit_created', ['created_at']);
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('audit_logs');
    }
}
