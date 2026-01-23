<?php

declare(strict_types=1);

namespace App\Migration;

use App\Shared\Enums\RecordStatus;
use App\Infrastructure\Time\AppDateTimeProvider;
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Psr\Clock\ClockInterface;
use Yiisoft\Db\Connection\Connection;

/**
 * Creates brand table with status tracking and JSON detail_info.
 */
final class M20240101000000CreateBrand implements RevertibleMigrationInterface
{
    public function __construct(
        private ClockInterface $clock
    ) {}

    public function up(MigrationBuilder $b): void
    {
        $cb = $b->columnBuilder();

        $b->createTable('brand', [
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
            'sync_mdb' => $cb::smallint()->null(),
        ]);

        $user = 'system';
        $dateTime = new AppDateTimeProvider($this->clock);
        $createdAt = $dateTime->iso8601();

        // Seed dummy data
        $dummyData = [
            ['name' => 'Asus', 'status' => RecordStatus::DRAFT->value],
            ['name' => 'Acer', 'status' => RecordStatus::DRAFT->value],
            ['name' => 'Intel', 'status' => RecordStatus::DRAFT->value],
            ['name' => 'AMD', 'status' => RecordStatus::DRAFT->value],
            ['name' => 'Klevv', 'status' => RecordStatus::DRAFT->value],
        ];

        foreach ($dummyData as $data) {
            $b->insert('brand', [
                'name'        => $data['name'],
                'status'      => $data['status'],
                'detail_info' => [
                    'change_log' => [
                        'created_at' => $createdAt,
                        'created_by' => $user,
                        'deleted_at' => null,
                        'deleted_by' => null,
                        'updated_at' => null,
                        'updated_by' => null,
                    ],
                ],
                'sync_mdb' => null,
            ]);
        }
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('brand');
    }
}
