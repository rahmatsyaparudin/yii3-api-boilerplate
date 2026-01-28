<?php

declare(strict_types=1);

namespace App\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

final class TemplateGeneratorCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('template:generate')
            ->setDescription('Generate new project from boilerplate template')
            ->addArgument('project-name', InputArgument::REQUIRED, 'Name of the new project')
            ->addArgument('target-path', InputArgument::REQUIRED, 'Target path for the new project')
            ->addOption('template-path', 't', InputOption::VALUE_OPTIONAL, 'Template source path', getcwd())
            ->addOption('exclude', 'e', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Exclude patterns', [
                'vendor',
                '.git',
                'runtime',
                'tests',
                '.env',
                'composer.lock',
                'node_modules'
            ])
            ->addOption('replace', 'r', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Replace patterns', [
                'yii3-api' => '{project_name}',
                'Yii3Api' => '{ProjectName}',
                'YII3_API' => '{PROJECT_NAME}',
                'rahmatsyaparudin/yii3-api' => '{vendor}/{project_name}',
                'Example' => '{Entity}',
                'example' => '{entity}',
                'Example' => '{Entity}',
                'example' => '{entity}',
            ])
            ->addOption('include-optimistic-lock', 'o', InputOption::VALUE_NONE, 'Include optimistic locking features')
            ->setHelp('This command allows you to generate a new project from the current boilerplate template');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $projectName = $input->getArgument('project-name');
        $targetPath = $input->getArgument('target-path');
        $templatePath = $input->getOption('template-path');
        $excludePatterns = $input->getOption('exclude');
        $replacePatterns = $input->getOption('replace');
        $includeOptimisticLock = $input->getOption('include-optimistic-lock');

        $io->title('Project Template Generator');
        $io->text("Generating project: <info>{$projectName}</info>");
        $io->text("Target path: <info>{$targetPath}</info>");
        $io->text("Template source: <info>{$templatePath}</info>");

        $filesystem = new Filesystem();
        
        // Validate template path
        if (!$filesystem->exists($templatePath)) {
            $io->error("Template path does not exist: {$templatePath}");
            return Command::FAILURE;
        }

        // Create target directory
        $targetDir = $targetPath . '/' . $projectName;
        if ($filesystem->exists($targetDir)) {
            if (!$io->confirm("Target directory exists. Overwrite?", false)) {
                $io->text('Operation cancelled.');
                return Command::SUCCESS;
            }
            $filesystem->remove($targetDir);
        }
        
        $filesystem->mkdir($targetDir);
        $io->text("Created target directory: {$targetDir}");

        // Find all files to copy
        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->ignoreDotFiles(false)
            ->in($templatePath);

        // Apply exclude patterns
        foreach ($excludePatterns as $pattern) {
            $finder->exclude($pattern);
            $finder->notPath($pattern);
        }

        $copiedFiles = 0;
        $processedFiles = 0;

        foreach ($finder as $file) {
            $relativePath = $file->getRelativePathname();
            $targetFile = $targetDir . '/' . $relativePath;

            // Create directory if needed
            $targetDirPath = dirname($targetFile);
            if (!$filesystem->exists($targetDirPath)) {
                $filesystem->mkdir($targetDirPath);
            }

            // Copy file
            $filesystem->copy($file->getPathname(), $targetFile);
            $copiedFiles++;

            // Process file content
            $this->processFile($targetFile, $replacePatterns, $projectName, $includeOptimisticLock, $io);
            $processedFiles++;
        }

        // Generate project-specific files
        $this->generateProjectSpecificFiles($targetDir, $projectName, $io);

        // Update composer.json
        $this->updateComposerJson($targetDir, $projectName, $io);

        $io->success("Project generated successfully!");
        $io->text("Copied files: {$copiedFiles}");
        $io->text("Processed files: {$processedFiles}");
        $io->text("Project location: {$targetDir}");
        
        $io->section('Next Steps');
        $io->text([
            "1. cd {$targetDir}",
            "2. composer install",
            "3. cp .env.example .env",
            "4. Configure your database",
            "5. ./yii migrate",
            "6. ./yii serve"
        ]);

        return Command::SUCCESS;
    }

    private function processFile(
        string $filePath, 
        array $replacePatterns, 
        string $projectName, 
        bool $includeOptimisticLock,
        SymfonyStyle $io
    ): void {
        $filesystem = new Filesystem();
        
        // Skip binary files
        $binaryExtensions = ['ico', 'jpg', 'jpeg', 'png', 'gif', 'pdf', 'zip', 'tar', 'gz'];
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if (in_array($extension, $binaryExtensions)) {
            return;
        }

        // Read file content
        $content = file_get_contents($filePath);
        if ($content === false) {
            return;
        }

        // Apply replacements
        $replacements = array_merge($replacePatterns, [
            '{project_name}' => strtolower($projectName),
            '{ProjectName}' => ucfirst($projectName),
            '{PROJECT_NAME}' => strtoupper($projectName),
            '{vendor}' => 'your-vendor', // You can make this configurable
        ]);

        foreach ($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }

        // Handle optimistic locking
        if (!$includeOptimisticLock) {
            $content = $this->removeOptimisticLocking($content);
        }

        // Write back
        file_put_contents($filePath, $content);
    }

    private function removeOptimisticLocking(string $content): string
    {
        // Remove optimistic locking related code
        $patterns = [
            '/\/\*\*[\s\S]*?\*\/\s*use.*OptimisticLock;/',
            '/use.*OptimisticLock;/',
            '/\/\*\*[\s\S]*?\*\/\s*use.*LockVersion;/',
            '/use.*LockVersion;/',
            '/\/\*\*[\s\S]*?\*\/\s*use.*OptimisticLockException;/',
            '/use.*OptimisticLockException;/',
            '/private LockVersion \$lockVersion;/',
            '/private \?LockVersion \$lockVersion;/',
            '/\$this->lockVersion[^;]*;/',
            '/->getLockVersion\(\)[^;]*;/',
            '/->upgradeVersion\(\)[^;]*;/',
            '/->verifyLockVersion\([^)]*\)[^;]*;/',
            '/lock_version[^,}]*[,}]/',
            '/LockVersion::[^;]*;/',
        ];

        foreach ($patterns as $pattern) {
            $content = preg_replace($pattern, '', $content);
        }

        return $content;
    }

    private function generateProjectSpecificFiles(string $targetDir, string $projectName, SymfonyStyle $io): void
    {
        $filesystem = new Filesystem();
        
        // Generate README.md
        $readmeContent = "# {$projectName}\n\nGenerated project from Yii3 API boilerplate.\n\n## Installation\n\n```bash\ncomposer install\n```\n\n## Configuration\n\n```bash\ncp .env.example .env\n```\n\n## Running\n\n```bash\n./yii serve\n```\n";
        
        file_put_contents($targetDir . '/README.md', $readmeContent);
        
        // Generate .env.example
        $envExample = "APP_ENV=dev\nAPP_DEBUG=1\nDB_DSN=pgsql:host=localhost;dbname={$projectName}\nDB_USERNAME=\nDB_PASSWORD=\nJWT_SECRET=\n";
        
        file_put_contents($targetDir . '/.env.example', $envExample);
        
        $io->text("Generated project-specific files");
    }

    private function updateComposerJson(string $targetDir, string $projectName, SymfonyStyle $io): void
    {
        $composerFile = $targetDir . '/composer.json';
        
        if (!file_exists($composerFile)) {
            return;
        }

        $composer = json_decode(file_get_contents($composerFile), true);
        
        // Update name
        $composer['name'] = 'your-vendor/' . strtolower($projectName);
        
        // Update description
        $composer['description'] = "Generated project: {$projectName}";
        
        // Remove boilerplate-specific scripts if any
        if (isset($composer['scripts']['template-generate'])) {
            unset($composer['scripts']['template-generate']);
        }
        
        // Write back
        file_put_contents($composerFile, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        $io->text("Updated composer.json");
    }
}
