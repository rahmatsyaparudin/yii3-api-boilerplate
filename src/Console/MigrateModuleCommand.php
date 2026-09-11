<?php

declare(strict_types=1);

namespace App\Console;

// Vendor Layer
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Runner\UpdateRunner;
use Yiisoft\Db\Migration\Service\MigrationService;

/**
 * Console command for applying migrations of a specific module.
 *
 * For example,
 *
 * ```shell
 * ./yii migrate:module example   # apply new migrations from App\Migration\Example
 * ```
 */
final class MigrateModuleCommand extends Command
{
    public function __construct(
        private readonly UpdateRunner $updateRunner,
        private readonly MigrationService $migrationService,
        private readonly Migrator $migrator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('migrate:module')
            ->setDescription('Applies new migrations for a specific module.')
            ->addArgument('module', InputArgument::REQUIRED, 'Module name (e.g., example).')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Number of migrations to apply.')
            ->addOption('force-yes', 'y', InputOption::VALUE_NONE, 'Force yes to all questions.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->migrator->setIo($io);
        $this->migrationService->setIo($io);
        $this->updateRunner->setIo($io);

        $this->migrationService->databaseConnection();

        $module    = \ucfirst((string) $input->getArgument('module'));
        $namespace = 'App\\Migration\\' . $module;

        // Restrict migration sources to the module namespace only
        $this->migrationService->setNewMigrationNamespace($namespace);
        $this->migrationService->setSourceNamespaces([$namespace]);

        try {
            $migrations = $this->migrationService->getNewMigrations();
        } catch (\LogicException) {
            $io->error("Migration directory for module '{$module}' not found (src/Migration/{$module}).");

            return Command::INVALID;
        }

        if (empty($migrations)) {
            $output->writeln("<fg=green>No new migrations found.</>\n");
            $io->success('Your system is up-to-date.');

            return Command::SUCCESS;
        }

        $limit = $input->getOption('limit');

        if ($limit !== null) {
            $limit = (int) $limit;

            if ($limit <= 0) {
                $io->error('The limit option must be greater than 0.');

                return Command::INVALID;
            }
        }

        $migrationsCount = \count($migrations);
        $migrationWord   = $migrationsCount === 1 ? 'migration' : 'migrations';

        if ($limit !== null && $migrationsCount > $limit) {
            $migrations = \array_slice($migrations, 0, $limit);

            $output->writeln("<fg=yellow>Total $limit out of $migrationsCount new $migrationWord to be applied:</>\n");
        } else {
            $output->writeln("<fg=yellow>Total $migrationsCount new $migrationWord to be applied:</>\n");
        }

        foreach ($migrations as $i => $migration) {
            $nameLimit = $this->migrator->getMigrationNameLimit();

            if (\strlen($migration) > $nameLimit) {
                $output->writeln(
                    "\n<fg=red>The migration name '$migration' is too long. Its not possible to apply "
                    . 'this migration.</>',
                );

                return Command::INVALID;
            }

            $output->writeln("\t<fg=yellow>" . ($i + 1) . ". $migration</>");
        }

        if ($input->getOption('force-yes') || $io->confirm("Apply the above $migrationWord?")) {
            $instances    = $this->migrationService->makeMigrations($migrations);
            $migrationWas = ($migrationsCount === 1 ? 'migration was' : 'migrations were');

            foreach ($instances as $i => $instance) {
                try {
                    $this->updateRunner->run($instance, $i + 1);
                } catch (\Throwable $e) {
                    $output->writeln("\n<fg=yellow>Total $i out of $migrationsCount new $migrationWas applied.</>\n");
                    $io->error($i > 0 ? 'Partially updated.' : 'Not updated.');

                    throw $e;
                }
            }

            $output->writeln("\n<fg=green>Total $migrationsCount new $migrationWas applied.</>\n");
            $io->success('Updated successfully.');
        }

        return Command::SUCCESS;
    }
}
