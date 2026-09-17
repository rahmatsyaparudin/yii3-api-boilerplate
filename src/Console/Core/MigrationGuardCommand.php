<?php

declare(strict_types=1);

namespace App\Console\Core;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

/**
 * Placeholder that replaces destructive migration commands (migrate:down,
 * migrate:redo) in the prod environment via `config/console/commands.php`.
 * Always fails so the real command can never run there.
 */
#[AsCommand(
    name: 'migration-guard',
    description: 'Disabled in the prod environment',
)]
final class MigrationGuardCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(
            \sprintf(
                '<error>"%s" is disabled in the "prod" environment.</error>',
                $this->getName() ?? 'this command',
            ),
        );

        return ExitCode::NOPERM;
    }
}
