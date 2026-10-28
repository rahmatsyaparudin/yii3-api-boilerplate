<?php

declare(strict_types=1);

namespace App\Migration\Example;

// Domain Layer
use App\Shared\Core\Enums\ColumnContract;
// Infrastructure Layer

// PSR Interfaces
use Psr\Clock\ClockInterface;
// Vendor Layer
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Creates another_example table with status tracking and JSON detail_info.
 */
final class M20260910104729CreateAnotherExampleTable implements RevertibleMigrationInterface
{
    private const TABLE_NAME = 'another_example';

    public function __construct(
        private ClockInterface $clock
    ) {
    }

    public function up(MigrationBuilder $b): void
    {
        $cb = $b->columnBuilder();

        $b->createTable(self::TABLE_NAME, [
            'id'          => $cb::integer()->notNull()->extra(ColumnContract::PG_PRIMARY_KEY_IDENTITY),
            'name'        => $cb::string(255)->notNull(),
            'example_id'  => $cb::integer()->notNull(),
            'status'      => $cb::smallint()->notNull()->defaultValue(ColumnContract::statusDefault())->comment(ColumnContract::statusComment()),
            'detail_info' => $cb::json()->notNull()->defaultValue(ColumnContract::DETAILINFO_DEFAULT)->comment(ColumnContract::DETAILINFO_COMMENT),
            'origin_id'    => $cb::integer()->null()->comment(ColumnContract::ORIGIN_ID_COMMENT),
            'sync_flag'    => $cb::smallint()->null()->defaultValue(ColumnContract::SYNC_FLAG_DEFAULT)->comment(ColumnContract::SYNC_FLAG_COMMENT),
            'sync_mdb'     => $cb::smallint()->null()->comment(ColumnContract::SYNC_MDB_COMMENT),
            'lock_version' => $cb::integer()->notNull()->defaultValue(ColumnContract::LOCK_VERSION_DEFAULT)->comment(ColumnContract::LOCK_VERSION_COMMENT),
        ]);
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable(self::TABLE_NAME);
    }
}
