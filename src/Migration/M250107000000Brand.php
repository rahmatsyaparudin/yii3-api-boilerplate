<?php

declare(strict_types=1);

namespace App\Migration;

use App\Shared\Constants\StatusEnum;
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Creates brand table with status tracking and JSON detail_info.
 */
final class M250107000000Brand implements RevertibleMigrationInterface
{
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

        // Seed dummy data
        $timestamp = \gmdate('Y-m-d H:i:s');
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
                        'created_at' => $timestamp,
                        'created_by' => 'system',
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
