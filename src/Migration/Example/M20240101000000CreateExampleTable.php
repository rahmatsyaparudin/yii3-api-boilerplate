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
 * Creates example table with status tracking and JSON detail_info.
 */
final class M20240101000000CreateExampleTable implements RevertibleMigrationInterface
{
    private const TABLE_NAME = 'example';

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
            'status'      => $cb::smallint()->notNull()->defaultValue(ColumnContract::statusDefault())->comment(ColumnContract::statusComment()),
            'detail_info' => $cb::json()->notNull()->defaultValue(ColumnContract::DETAIL_INFO_DEFAULT)->comment(ColumnContract::DETAIL_INFO_COMMENT),
            'sync_mdb'     => $cb::smallint()->null()->comment(ColumnContract::SYNC_MDB_COMMENT),
            'lock_version' => $cb::integer()->notNull()->defaultValue(ColumnContract::LOCK_VERSION_DEFAULT)->comment(ColumnContract::LOCK_VERSION_COMMENT),
        ]);
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable(self::TABLE_NAME);
    }
}
