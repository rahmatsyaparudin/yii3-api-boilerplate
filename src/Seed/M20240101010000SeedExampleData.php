<?php

declare(strict_types=1);

namespace App\Seed;

// Domain Layer
use App\Shared\Enums\RecordStatus;

// Infrastructure Layer
use App\Infrastructure\Time\AppDateTimeProvider;

// PSR Interfaces
use Psr\Clock\ClockInterface;

// Vendor Layer
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Seeds example table with initial data.
 */
final class M20240101010000SeedExampleData implements RevertibleMigrationInterface
{
    public function __construct(
        private ClockInterface $clock
    ) {}

    public function up(MigrationBuilder $b): void
    {
        // Check if running in development environment
        $appEnv = $_ENV['APP_ENV'] ?? '';
        if (!in_array($appEnv, ['dev', 'development'], true)) {
            echo "Seed migration skipped: Can only be run in development environment.\n";
            echo "Current environment: {$appEnv}\n";
            return;
        }

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
            $b->insert('example', [
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
                'sync_mdb'    => null,
                'lock_version'=> 1,
            ]);
        }
    }

    public function down(MigrationBuilder $b): void
    {
        // Remove seeded data
        $b->delete('example', ['name' => ['Asus', 'Acer', 'Intel', 'AMD', 'Klevv']]);
    }
}
