<?php

declare(strict_types=1);

namespace App\Console\Core;

// Vendor Layer
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Console command for injecting the standard sync columns
 * ({@see \App\Shared\Core\Enums\SyncColumns}) into migrations.
 *
 * ```shell
 * # Forgot the sync columns — generate an ALTER migration:
 * ./yii sync:columns add orders Example
 *
 * # Patch an existing create-table migration in place:
 * ./yii sync:columns inject src/Migration/Example/M20240101000000CreateOrdersTable.php
 * ```
 */
final class SyncColumnsCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('sync:columns')
            ->setDescription('Inject standard sync columns (uuid, source_store_id, origin_id, sync_flag, doc_role, lock_version) into migrations.')
            ->addArgument('action', InputArgument::REQUIRED, "add = generate an ALTER migration | inject = patch an existing create-table migration")
            ->addArgument('target', InputArgument::REQUIRED, 'add: table name | inject: path to the migration file')
            ->addArgument('module', InputArgument::OPTIONAL, 'Migration module namespace (e.g., Example). Required for add.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $action = (string) $input->getArgument('action');
        $target = (string) $input->getArgument('target');

        return match ($action) {
            'add'    => $this->generateAddMigration($io, $target, $input->getArgument('module')),
            'inject' => $this->injectIntoCreate($io, $target),
            default  => $this->invalidAction($io, $action),
        };
    }

    private function invalidAction(SymfonyStyle $io, string $action): int
    {
        $io->error("Unknown action '{$action}'. Use 'add' or 'inject'.");

        return Command::INVALID;
    }

    /**
     * Generate an ALTER migration adding the sync columns to an existing table.
     */
    private function generateAddMigration(SymfonyStyle $io, string $table, mixed $module): int
    {
        if (!\is_string($module) || $module === '') {
            $io->error("The 'module' argument is required for 'add' (e.g., ./yii sync:columns add orders Example).");

            return Command::INVALID;
        }

        $module = \ucfirst($module);
        $dir = 'src/Migration/' . $module;

        if (!\is_dir($dir) && !\mkdir($dir, 0755, true)) {
            $io->error("Cannot create migration directory {$dir}.");

            return Command::FAILURE;
        }

        $studly = \str_replace(' ', '', \ucwords(\str_replace(['-', '_'], ' ', $table)));
        $class  = 'M' . \date('YmdHis') . 'AddSyncColumnsTo' . $studly . 'Table';
        $file   = $dir . '/' . $class . '.php';

        $template = <<<PHP
            <?php

            declare(strict_types=1);

            namespace App\\Migration\\{$module};

            // Domain Layer
            use App\\Shared\\Core\\Enums\\SyncColumns;
            // PSR Interfaces
            use Psr\\Clock\\ClockInterface;
            // Vendor Layer
            use Yiisoft\\Db\\Migration\\MigrationBuilder;
            use Yiisoft\\Db\\Migration\\RevertibleMigrationInterface;

            /**
             * Adds the standard sync columns to {$table} (uuid, source_store_id,
             * origin_id, sync_flag, doc_role, lock_version).
             */
            final class {$class} implements RevertibleMigrationInterface
            {
                private const TABLE_NAME = '{$table}';

                public function __construct(
                    private ClockInterface \$clock
                ) {
                }

                public function up(MigrationBuilder \$b): void
                {
                    \$cb = \$b->columnBuilder();

                    // NOTE: `uuid` is NOT NULL + UNIQUE. On a populated table, backfill
                    // one uuid per existing row first (pgsql: gen_random_uuid(),
                    // mysql: UUID()), e.g. add the column nullable, fill, then
                    // SET NOT NULL — adjust the definition below if needed.
                    foreach (SyncColumns::all(\$cb) as \$column => \$definition) {
                        \$b->addColumn(self::TABLE_NAME, \$column, \$definition);
                    }
                }

                public function down(MigrationBuilder \$b): void
                {
                    foreach (SyncColumns::names() as \$column) {
                        \$b->dropColumn(self::TABLE_NAME, \$column);
                    }
                }
            }

            PHP;

        \file_put_contents($file, $template);

        $io->success("Created {$file}");
        $io->note("Review the migration — on populated tables, `uuid` needs a per-row backfill before NOT NULL can be enforced.");

        return Command::SUCCESS;
    }

    /**
     * Patch an existing create-table migration: inserts
     * `...SyncColumns::all($cb),` as the last entry of the first
     * createTable() column array, plus the matching `use` statement.
     */
    private function injectIntoCreate(SymfonyStyle $io, string $file): int
    {
        if (!\is_file($file)) {
            $io->error("File not found: {$file}");

            return Command::INVALID;
        }

        $content = (string) \file_get_contents($file);

        if (\str_contains($content, 'SyncColumns::')) {
            $io->note('SyncColumns is already injected in this file — nothing to do.');

            return Command::SUCCESS;
        }

        if (!\str_contains($content, 'createTable(')) {
            $io->error('No createTable() call found in this migration — nothing to inject into. Use "add" for an existing table.');

            return Command::INVALID;
        }

        if (!\str_contains($content, '$cb')) {
            $io->error('This migration does not use the $cb column builder — inject the columns manually.');

            return Command::INVALID;
        }

        $patched = \preg_replace_callback(
            '/(\$b->createTable\([\s\S]*?)(\n(\s*)\]\);)/',
            static function (array $m): string {
                $itemIndent = $m[3] . '    ';
                return $m[1] . "\n" . $itemIndent . '...SyncColumns::all($cb),' . $m[2];
            },
            $content,
            1
        );

        if ($patched === null || $patched === $content) {
            $io->error('Could not locate the closing of the createTable() column array — file unchanged.');

            return Command::FAILURE;
        }

        $patched = self::addUseStatement($patched);

        if (\file_put_contents($file, $patched) === false) {
            $io->error("Cannot write to {$file}.");

            return Command::FAILURE;
        }

        $lint = [];
        \exec('php -l ' . \escapeshellarg($file) . ' 2>&1', $lint, $lintCode);
        if ($lintCode !== 0) {
            \file_put_contents($file, $content);
            $io->error('Patched file failed php -l — reverted. Inject the columns manually.');

            return Command::FAILURE;
        }

        $io->success("Injected ...SyncColumns::all(\$cb) into {$file}");

        return Command::SUCCESS;
    }

    private static function addUseStatement(string $content): string
    {
        $use = 'use App\Shared\Core\Enums\SyncColumns;';

        if (\str_contains($content, $use)) {
            return $content;
        }

        // Prefer grouping next to the ColumnContract import used by migrations.
        if (\str_contains($content, 'use App\Shared\Core\Enums\ColumnContract;')) {
            return \str_replace(
                'use App\Shared\Core\Enums\ColumnContract;',
                "use App\\Shared\\Core\\Enums\\ColumnContract;\n{$use}",
                $content
            );
        }

        // Otherwise append after the last use statement in the header.
        return (string) \preg_replace(
            '/\n(final\s+class\s)/',
            "\n{$use}\n\n$1",
            $content,
            1
        );
    }
}
