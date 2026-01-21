<?php

declare(strict_types=1);

namespace App\Migration;

use App\Shared\Constants\StatusEnum;
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
            'status'      => $cb::smallint()->notNull()->defaultValue(StatusEnum::ACTIVE->value),
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
            ['name' => 'Asus', 'status' => StatusEnum::ACTIVE->value],
            ['name' => 'Acer', 'status' => StatusEnum::ACTIVE->value],
            ['name' => 'Intel', 'status' => StatusEnum::ACTIVE->value],
            ['name' => 'AMD', 'status' => StatusEnum::ACTIVE->value],
            ['name' => 'Klevv', 'status' => StatusEnum::ACTIVE->value],
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
