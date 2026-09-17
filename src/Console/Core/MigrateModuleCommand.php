<?php

declare(strict_types=1);

namespace App\Console\Core;

// Application Layer
use App\Infrastructure\Core\Database\ConnectionPool;
// PSR Interfaces
use Psr\Container\ContainerInterface;
// Vendor Layer
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Db\Migration\Informer\MigrationInformerInterface;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Runner\UpdateRunner;
use Yiisoft\Db\Migration\Service\MigrationService;
use Yiisoft\Injector\Injector;

/**
 * Console command for applying migrations of a specific module.
 *
 * For example,
 *
 * ```shell
 * ./yii migrate:module example              # migrate App\Migration\Example on db.default.*
 * ./yii migrate:module auditable --db=audit # migrate App\Migration\Auditable on db.audit.*
 * ```
 *
 * Every module must be mapped to a connection in
 * `moduleConnections` (config/common/migration.php);
 * without a mapping the command fails. `--db` overrides the mapping.
 */
final class MigrateModuleCommand extends Command
{
    /**
     * @param array<string, string> $moduleConnections Module name => connection name.
     */
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly ContainerInterface $container,
        private readonly MigrationInformerInterface $informer,
        private readonly array $moduleConnections = [],
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
            ->addOption('db', null, InputOption::VALUE_REQUIRED, 'Connection name (db.<name>.* env keys). Overrides the module\'s entry in config/common/migration.php moduleConnections.')
            ->addOption('force-yes', 'y', InputOption::VALUE_NONE, 'Force yes to all questions.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $module = \ucfirst((string) $input->getArgument('module'));

        $connectionName = $input->getOption('db') ?? $this->moduleConnections[$module] ?? null;

        if ($connectionName === null) {
            $io->error(
                "No database mapping for module '{$module}'. Add '{$module}' => '<connection>' to "
                . "'moduleConnections' in config/common/migration.php, "
                . "or pass --db=<name>."
            );

            return Command::INVALID;
        }

        $db = $this->connectionPool->get((string) $connectionName);

        $migrator         = new Migrator($db, $this->informer);
        $migrationService = new MigrationService($db, new Injector($this->container), $migrator);
        $updateRunner     = new UpdateRunner($migrator);

        $migrator->setIo($io);
        $migrationService->setIo($io);
        $updateRunner->setIo($io);

        $io->writeln("<fg=cyan>Database connection: {$connectionName} ({$db->getDriverName()}).</>");

        $namespace = 'App\\Migration\\' . $module;

        // Restrict migration sources to the module namespace only
        $migrationService->setNewMigrationNamespace($namespace);
        $migrationService->setSourceNamespaces([$namespace]);

        try {
            $migrations = $migrationService->getNewMigrations();
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
            $nameLimit = $migrator->getMigrationNameLimit();

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
            $instances    = $migrationService->makeMigrations($migrations);
            $migrationWas = ($migrationsCount === 1 ? 'migration was' : 'migrations were');

            foreach ($instances as $i => $instance) {
                try {
                    $updateRunner->run($instance, $i + 1);
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
