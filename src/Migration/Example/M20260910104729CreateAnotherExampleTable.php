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
            'detail_info' => $cb::json()->notNull()->defaultValue(ColumnContract::DETAIL_INFO_DEFAULT)->comment(ColumnContract::detailInfoComment()),
            'origin_id'    => $cb::integer()->null()->comment(ColumnContract::originIdComment()),
            'sync_flag'    => $cb::smallint()->null()->defaultValue(ColumnContract::SYNC_FLAG_DEFAULT)->comment(ColumnContract::syncFlagComment()),
            'sync_mdb'     => $cb::smallint()->null()->comment(ColumnContract::syncMdbComment()),
            'lock_version' => $cb::integer()->notNull()->defaultValue(ColumnContract::LOCK_VERSION_DEFAULT)->comment(ColumnContract::lockVersionComment()),
        ]);
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable(self::TABLE_NAME);
    }
}
