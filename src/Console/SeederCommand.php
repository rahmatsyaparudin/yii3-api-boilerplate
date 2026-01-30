<?php

declare(strict_types=1);

namespace App\Console;

// PSR Interfaces
use Psr\Container\ContainerInterface;

// Vendor Layer
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console command for seeding data.
 */
final class SeederCommand extends Command
{
    public function __construct(
        private ContainerInterface $container
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('seed')
            ->setDescription('Seed all available seeders or specific module')
            ->addOption(
                'module',
                'm',
                InputOption::VALUE_OPTIONAL,
                'Specific module to seed (e.g., example, product)',
                null
            )
            ->addOption(
                'count',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Number of records to seed (default: 10)',
                10
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Check if running in development environment
        if (!in_array($_ENV['APP_ENV'] ?? '', ['dev', 'development'], true)) {
            $output->writeln('<error>Seeding is only allowed in development environment!</error>');
            return Command::FAILURE;
        }

        $module = $input->getOption('module');
        $count = (int) $input->getOption('count');
        
        if ($module) {
            // Seed specific module
            $this->seedModule($module, $count, $output);
        } else {
            // Seed all available modules
            $this->seedAll($count, $output);
        }

        return Command::SUCCESS;
    }

    private function seedModule(string $module, int $count, OutputInterface $output): void
    {
        $seederClass = "App\\Seeder\\Seed" . ucfirst($module) . "Data";
        
        if (!class_exists($seederClass)) {
            $output->writeln("<error>Seeder not found: {$seederClass}</error>");
            return;
        }

        try {
            $seeder = $this->container->get($seederClass);
            $seeder->run($count);  // Pass count to seeder
            $output->writeln("<info>✅ {$module} data seeded successfully!</info>");
        } catch (\Exception $e) {
            $output->writeln("<error>❌ Error seeding {$module}: {$e->getMessage()}</error>");
        }
    }

    private function seedAll(int $count, OutputInterface $output): void
    {
        $seedDir = __DIR__ . '/../Seeder';
        $seedFiles = glob($seedDir . '/Seed*Data.php');
        
        if (empty($seedFiles)) {
            $output->writeln('<comment>No seeders found in ' . $seedDir . '</comment>');
            return;
        }

        $output->writeln('<info>Found ' . count($seedFiles) . ' seeders:</info>');
        
        foreach ($seedFiles as $seedFile) {
            $className = basename($seedFile, '.php');
            $module = strtolower(str_replace(['Seed', 'Data'], '', $className));
            
            $this->seedModule($module, $count, $output);
        }
    }
}
