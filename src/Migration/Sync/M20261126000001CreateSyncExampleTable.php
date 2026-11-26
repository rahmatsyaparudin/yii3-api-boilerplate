<?php

declare(strict_types=1);

namespace App\Migration\Sync;

// Domain Layer
use App\Shared\Core\Enums\ColumnContract;
use App\Shared\Core\Enums\SyncColumns;
// Vendor Layer
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Creates sync_example — reference module for the full sync column set.
 *
 * Demonstrates `SyncColumns::all()` injection: the canonical tail
 * (lock_version, sync_mdb, sync_flag, origin_id, source_store_id, uuid,
 * doc_role) is spread after detail_info instead of hand-written columns.
 */
final class M20261126000001CreateSyncExampleTable implements RevertibleMigrationInterface
{
    private const TABLE_NAME = 'sync_example';

    public function up(MigrationBuilder $b): void
    {
        $cb = $b->columnBuilder();

        $b->createTable(self::TABLE_NAME, [
            'id'          => $cb::integer()->notNull()->extra(ColumnContract::PG_PRIMARY_KEY_IDENTITY),
            'name'        => $cb::string(255)->notNull(),
            'status'      => $cb::smallint()->notNull()->defaultValue(ColumnContract::statusDefault())->comment(ColumnContract::statusComment()),
            'detail_info' => $cb::json()->notNull()->defaultValue(ColumnContract::DETAILINFO_DEFAULT)->comment(ColumnContract::DETAILINFO_COMMENT),
            ...SyncColumns::all($cb),
        ]);
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable(self::TABLE_NAME);
    }
}
