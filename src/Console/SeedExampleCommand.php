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
 * Console command for seeding example data.
 */
final class SeedExampleCommand extends Command
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
            ->setName('seed:example')
            ->setDescription('Seed example table with initial data')
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

        $output->writeln('<info>Seeding example data...</info>');

        if ($truncate) {
            $output->writeln('<info>Truncating example table...</info>');
            $this->db->createCommand()->truncateTable('example')->execute();
        }

        $dateTime = new AppDateTimeProvider($this->clock);
        $createdAt = $dateTime->iso8601();
        $user = 'system';

        // Generate seed data
        $examples = $this->generateExampleData($count);

        foreach ($examples as $data) {
            $this->db->createCommand()->insert('example', [
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

        $output->writeln("<info>Successfully seeded {$count} example records.</info>");

        return Command::SUCCESS;
    }

    /**
     * Generate example data.
     *
     * @param int $count Number of records to generate
     * @return array<array{name: string, status: int}>
     */
    private function generateExampleData(int $count): array
    {
        $defaultExamples = [
            ['name' => 'Asus', 'status' => RecordStatus::DRAFT->value],
            ['name' => 'Acer', 'status' => RecordStatus::DRAFT->value],
            ['name' => 'Intel', 'status' => RecordStatus::DRAFT->value],
            ['name' => 'AMD', 'status' => RecordStatus::DRAFT->value],
            ['name' => 'Klevv', 'status' => RecordStatus::DRAFT->value],
        ];

        if ($count <= count($defaultExamples)) {
            return array_slice($defaultExamples, 0, $count);
        }

        // Generate additional random examples if needed
        $additionalExamples = [];
        for ($i = count($defaultExamples); $i < $count; $i++) {
            $additionalExamples[] = [
                'name' => 'Example ' . ($i + 1),
                'status' => RecordStatus::DRAFT->value,
            ];
        }

        return array_merge($defaultExamples, $additionalExamples);
    }
}
