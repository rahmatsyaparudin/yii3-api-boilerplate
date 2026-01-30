<?php

declare(strict_types=1);

namespace App\Console;

// Domain Layer
use App\Shared\Enums\RecordStatus;

// Infrastructure Layer
use App\Infrastructure\Time\AppDateTimeProvider;

// PSR Interfaces
use Psr\Clock\ClockInterface;

// Vendor Layer
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * Console command for seeding product data.
 */
final class SeedProductCommand extends Command
{
    public function __construct(
        private ClockInterface $clock,
        private ConnectionInterface $db
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('seed:product')
            ->setDescription('Seed product table with initial data')
            ->addOption(
                'truncate',
                't',
                InputOption::VALUE_NONE,
                'Truncate table before seeding'
            )
            ->addOption(
                'count',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Number of records to seed',
                '5'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Check if running in development environment
        if (!in_array($_ENV['APP_ENV'] ?? '', ['dev', 'development'], true)) {
            $output->writeln('<error>Seed command can only be run in development environment.</error>');
            $output->writeln('<error>Current environment: ' . ($_ENV['APP_ENV'] ?? 'unknown') . '</error>');
            return Command::FAILURE;
        }

        $truncate = $input->getOption('truncate');
        $count = (int) $input->getOption('count');

        $output->writeln('<info>Seeding product data...</info>');

        if ($truncate) {
            $output->writeln('<info>Truncating product table...</info>');
            $this->db->createCommand()->truncateTable('product')->execute();
        }

        $dateTime = new AppDateTimeProvider($this->clock);
        $createdAt = $dateTime->iso8601();
        $user = 'system';

        // Generate seed data
        $products = $this->generateProductData($count);

        foreach ($products as $data) {
            $this->db->createCommand()->insert('product', [
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
            ])->execute();
        }

        $output->writeln("<info>Successfully seeded {$count} product records.</info>");

        return Command::SUCCESS;
    }

    /**
     * Generate product data.
     *
     * @param int $count Number of records to generate
     * @return array<array{name: string, status: int}>
     */
    private function generateProductData(int $count): array
    {
        $defaultProducts = [
            ['name' => 'Asus', 'status' => RecordStatus::DRAFT->value],
            ['name' => 'Acer', 'status' => RecordStatus::DRAFT->value],
            ['name' => 'Intel', 'status' => RecordStatus::DRAFT->value],
            ['name' => 'AMD', 'status' => RecordStatus::DRAFT->value],
            ['name' => 'Klevv', 'status' => RecordStatus::DRAFT->value],
        ];

        if ($count <= count($defaultProducts)) {
            return array_slice($defaultProducts, 0, $count);
        }

        // Generate additional random products if needed
        $additionalProducts = [];
        for ($i = count($defaultProducts); $i < $count; $i++) {
            $additionalProducts[] = [
                'name' => 'Product ' . ($i + 1),
                'status' => RecordStatus::DRAFT->value,
            ];
        }

        return array_merge($defaultProducts, $additionalProducts);
    }
}
