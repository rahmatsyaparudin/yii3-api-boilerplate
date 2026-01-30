<?php

declare(strict_types=1);

namespace App\Migration;

// Domain Layer
use App\Shared\Enums\RecordStatus;

// Infrastructure Layer
use App\Infrastructure\Time\AppDateTimeProvider;

// PSR Interfaces
use Psr\Clock\ClockInterface;

// Vendor Layer
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Connection\Connection;

/**
 * Creates --help table with status tracking and JSON detail_info.
 */
final class M20260130123028Create--helpTable implements RevertibleMigrationInterface
{
    private const TABLE_NAME = '--help';
    
    public function __construct(
        private ClockInterface $clock
    ) {}

    public function up(MigrationBuilder $b): void
    {
        $cb = $b->columnBuilder();

        $b->createTable(self::TABLE_NAME, [
            'id'          => $cb::primaryKey(),
            'name'        => $cb::string(255)->notNull(),
            'status'      => $cb::smallint()->notNull()->defaultValue(RecordStatus::DRAFT->value),
            'detail_info' => $cb::json()->notNull()->defaultValue([
                'change_log' => [
                    'created_at' => null,
                    'created_by' => null,
                    'deleted_at' => null,
                    'deleted_by' => null,
                    'updated_at' => null,
                    'updated_by' => null,
                ],
            ]),
            'sync_mdb'    => $cb::smallint()->null(),
            'lock_version'=> $cb::integer()->notNull()->defaultValue(1)->comment('Optimistic locking version'),
        ]);
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable(self::TABLE_NAME);
    }
}
