<?php

declare(strict_types=1);

namespace App\Migration\Sync;

// Domain Layer
use App\Shared\Core\Enums\ColumnContract;
// Vendor Layer
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Creates sync_queue — outbox table for record synchronization.
 *
 * One business record → one queue row per destination store. Writers
 * (repository/service) insert rows in the same transaction as the business
 * write; a puller reads `status = PENDING AND destination_store_id =
 * <self-or-null>` (null = broadcast), then the receiver acks per row.
 * `attempts`/`last_error` track retries; `direction` mirrors
 * SyncDirection for routing context.
 */
final class M20261126000000CreateSyncQueueTable implements RevertibleMigrationInterface
{
    private const TABLE_NAME = 'sync_queue';

    public function up(MigrationBuilder $b): void
    {
        $cb = $b->columnBuilder();

        $b->createTable(self::TABLE_NAME, [
            'id'                   => $cb::integer()->notNull()->extra(ColumnContract::PG_PRIMARY_KEY_IDENTITY),
            'table_name'           => $cb::string(100)->notNull()->comment('Source business table of the record'),
            'record_uuid'          => $cb::string(36)->notNull()->comment('Global record identifier (SyncIdentity uuid)'),
            'destination_store_id' => $cb::integer()->null()->comment('Target store/origin id; null = broadcast to all'),
            'operation'            => $cb::string(8)->notNull()->comment('INSERT | UPDATE | DELETE'),
            'direction'            => $cb::smallint()->notNull()->defaultValue(0)->comment('SyncDirection value (0-3)'),
            'payload'              => $cb::json()->null()->comment('Record snapshot carried to the destination'),
            'status'               => $cb::smallint()->notNull()->defaultValue(1)->comment('SyncQueueStatus: 1 pending, 2 sent, 3 acked, 4 failed'),
            'attempts'             => $cb::smallint()->notNull()->defaultValue(0)->comment('Delivery retry counter'),
            'last_error'           => $cb::text()->null()->comment('Last delivery/apply error message'),
            'synced_at'            => $cb::datetime()->null()->comment('When the row was acked by the destination'),
            'created_at'           => $cb::datetime(),
            'created_by'           => $cb::string(100)->null(),
        ]);

        $b->createIndex(self::TABLE_NAME, 'idx_status_destination', ['status', 'destination_store_id']);
        $b->createIndex(self::TABLE_NAME, 'idx_record', ['table_name', 'record_uuid']);
        $b->createIndex(self::TABLE_NAME, 'idx_record_uuid', ['record_uuid']);
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable(self::TABLE_NAME);
    }
}
