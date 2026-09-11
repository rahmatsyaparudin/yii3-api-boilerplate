#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Composer Script: Install Skeleton
 * 
 * This script is called via composer install-skeleton command
 * It sets up the project from boilerplate template
 */

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

require_once __DIR__ . '/../vendor/autoload.php';

class SkeletonInstaller
{
    private Filesystem $filesystem;
    private string $projectRoot;
    private string $vendorPath;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
        $this->projectRoot = dirname(__DIR__);
        $this->vendorPath = $this->projectRoot . '/vendor/rahmatsyaparudin/yii3-api-boilerplate';
    }

    public function install(): void
    {
        echo "🚀 Installing Shared classes from vendor...\n";

        // Copy scripts first so the latest installer logic is used
        $this->copyScripts();

        // Only copy Shared classes from vendor
        $this->copySharedClasses();
        
        // Copy Infrastructure classes from vendor
        $this->copyInfrastructureClasses();
        
        // Copy Domain Shared classes from vendor
        $this->copyDomainSharedClasses();
        
        // Copy Application Shared classes from vendor
        $this->copyApplicationSharedClasses();
        
        // Copy API Shared classes from vendor
        $this->copyApiSharedClasses();
        
        // Copy Config files from vendor
        $this->copyConfigFiles();
        
        // Copy Message files from vendor
        $this->copyMessageFiles();
        
        // Copy API files from vendor
        $this->copyApiFiles();
        
        // Create empty directories for migrations and seeds
        $this->createEmptyDirectories();
        
        // Copy Quality Assurance script
        $this->copyQualityScript();
        
        // Update composer.json with required packages
        $this->updateComposerJson();
        
        echo "✅ Shared classes installation completed!\n";
        echo "\n🎯 Shared classes copied to src/Shared/\n";
        echo "📁 Directories created: Core/{Dto, Enums, ErrorHandler, Exception, Middleware, Query, Request, Security, Utility, Validation, ValueObject}, Common/Context\n";
        echo "🏗️  Infrastructure classes copied to src/Infrastructure/\n";
        echo "📁 Directories created: Core/{Audit, Clock, Concerns, Database, Monitoring, RateLimit, Security, Seeder, Time}, Common/Persistence\n";
        echo "🧠 Domain Shared classes copied to src/Domain/Shared/\n";
        echo "📁 Directories created: Core/{Audit, Concerns, Contract, Security, ValueObject}, Common\n";
        echo "⚙️  Application Shared classes copied to src/Application/Shared/\n";
        echo "📁 Directories created: Core/Factory, Common\n";
        echo "🌐 API Shared classes copied to src/Api/Shared/\n";
        echo "📁 Directories created: Presenter, ExceptionResponderFactory.php, ResponseFactory.php\n";
        echo "⚙️  Config files copied to config/\n";
        echo "📁 Files copied: config/common/di/*, config/web/di/*, config/console/*\n";
        echo "💬 Message files copied to resources/messages/\n";
        echo "📁 Message files copied to resources/messages/ for all languages (app.php skipped)\n";
        echo "🌐 API files copied to src/Api/\n";
        echo "📁 Files copied: IndexAction.php\n";
        echo "🔧 Autoload file copied to src/\n";
        echo "📁 Files copied: autoload.php\n";
        echo "📁 Empty directories created: src/Migration, src/Seed\n";
        echo "🖥️  Console commands copied to src/Console/\n";
        echo "📁 Files copied: HelloCommand.php, MigrateModuleCommand.php, SeederCommand.php\n";
        echo "🔧 Quality Assurance script copied to project root\n";
        echo "📁 Files copied: quality\n";
        echo "📦 Composer packages updated in composer.json\n";
        echo "📁 Packages added: firebase/php-jwt, psr/clock, vlucas/phpdotenv, yiisoft/* packages\n";

        $versionFile = $this->projectRoot . '/scripts/skeleton.version';
        $version = file_exists($versionFile) ? trim((string) file_get_contents($versionFile)) : 'unknown';
        echo "\n\033[1;92m✨ Skeleton updated successfully to version {$version}\033[0m\n";
    }

    private function copyScripts(): void
    {
        // Scripts are only copied from the vendor package (no project fallback)
        $vendorScriptsPath = $this->vendorPath . '/scripts';

        if (!is_dir($vendorScriptsPath)) {
            return;
        }

        // Compare scripts/skeleton.version instead of comparing files
        $vendorVersionFile  = $vendorScriptsPath . '/skeleton.version';
        $currentVersionFile = $this->projectRoot . '/scripts/skeleton.version';

        if (!file_exists($vendorVersionFile)) {
            return;
        }

        $vendorVersion  = trim((string) file_get_contents($vendorVersionFile));
        $currentVersion = file_exists($currentVersionFile)
            ? trim((string) file_get_contents($currentVersionFile))
            : null;

        if ($vendorVersion === '' || $vendorVersion === $currentVersion) {
            return;
        }

        echo "\033[1;93m📜 Updating skeleton scripts: {$currentVersion} → {$vendorVersion}\033[0m\n";

        $targetScriptsPath = $this->projectRoot . '/scripts';
        if (!is_dir($targetScriptsPath)) {
            mkdir($targetScriptsPath, 0755, true);
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($vendorScriptsPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $sourcePath   = $file->getPathname();
            $relativePath = str_replace($vendorScriptsPath, '', $sourcePath);
            $targetPath   = $targetScriptsPath . $relativePath;

            if ($file->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
                continue;
            }

            $targetDir = dirname($targetPath);
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            copy($sourcePath, $targetPath);
            echo "✅ Copied script: scripts" . str_replace('\\', '/', $relativePath) . "\n";
        }

        // Version changed — restart once so the new installer logic applies immediately
        if (getenv('SKELETON_SELF_UPDATED') !== '1') {
            echo "🔄 Skeleton updated to {$vendorVersion} — restarting with the new installer...\n";
            putenv('SKELETON_SELF_UPDATED=1');
            passthru(PHP_BINARY . ' ' . escapeshellarg($targetScriptsPath . '/skeleton-update.php'), $exitCode);
            exit($exitCode);
        }
    }

    private function copySharedClasses(): void
    {
        // In actual vendor package usage, copy from vendor to project
        $vendorSharedPath = $this->vendorPath . '/src/Shared';
        $targetSharedPath = $this->projectRoot . '/src/Shared';
        
        // Ensure Shared directory exists
        if (!is_dir($targetSharedPath)) {
            mkdir($targetSharedPath, 0755, true);
        }
        
        // Create all required subdirectories
        $sharedDirs = [
            'Core/Dto',
            'Core/Enums',
            'Core/ErrorHandler',
            'Core/Exception',
            'Core/Middleware',
            'Core/Query',
            'Core/Request',
            'Core/Security',
            'Core/Utility',
            'Core/Validation',
            'Core/ValueObject',
            'Common',
            'Common/Context',
        ];
        
        foreach ($sharedDirs as $dir) {
            $dirPath = $targetSharedPath . '/' . $dir;
            if (!is_dir($dirPath)) {
                mkdir($dirPath, 0755, true);
                echo "✅ Created directory: src/Shared/{$dir}\n";
            }
        }
        
        // Copy Shared classes from vendor if available
        if (is_dir($vendorSharedPath)) {
            $this->copyDirectory($vendorSharedPath, $targetSharedPath);
            echo "✅ Copied Shared classes from vendor\n";
        } else {
            // Fallback: copy from current location (for testing in boilerplate)
            $currentSharedPath = $this->projectRoot . '/src/Shared';
            if (is_dir($currentSharedPath)) {
                $this->copyDirectory($currentSharedPath, $targetSharedPath);
                echo "✅ Copied existing Shared classes\n";
            }
        }

        if (file_exists($targetSharedPath . '/ApplicationParams.php')) {
            echo "✅ ApplicationParams.php copied to src/Shared/\n";
        }
    }

    private function copyInfrastructureClasses(): void
    {
        // In actual vendor package usage, copy from vendor to project
        $vendorInfrastructurePath = $this->vendorPath . '/src/Infrastructure';
        $targetInfrastructurePath = $this->projectRoot . '/src/Infrastructure';
        
        // Ensure Infrastructure directory exists
        if (!is_dir($targetInfrastructurePath)) {
            mkdir($targetInfrastructurePath, 0755, true);
        }
        
        // Create all required subdirectories
        $infrastructureDirs = [
            'Core/Audit',
            'Core/Clock',
            'Core/Concerns',
            'Core/Database',
            'Core/Monitoring',
            'Core/RateLimit',
            'Core/Security',
            'Core/Time',
            'Core/Seeder',
            'Common/Persistence',
        ];
        
        foreach ($infrastructureDirs as $dir) {
            $dirPath = $targetInfrastructurePath . '/' . $dir;
            if (!is_dir($dirPath)) {
                mkdir($dirPath, 0755, true);
                echo "✅ Created directory: src/Infrastructure/{$dir}\n";
            }
        }
        
        // Copy Infrastructure classes from vendor if available
        if (is_dir($vendorInfrastructurePath)) {
            $this->copyDirectory($vendorInfrastructurePath, $targetInfrastructurePath);
            echo "✅ Copied Infrastructure classes from vendor\n";
        } else {
            // Fallback: copy from current location (for testing in boilerplate)
            $currentInfrastructurePath = $this->projectRoot . '/src/Infrastructure';
            if (is_dir($currentInfrastructurePath)) {
                $this->copyDirectory($currentInfrastructurePath, $targetInfrastructurePath);
                echo "✅ Copied existing Infrastructure classes\n";
            }
        }
    }

    private function copyDomainSharedClasses(): void
    {
        // In actual vendor package usage, copy from vendor to project
        $vendorDomainSharedPath = $this->vendorPath . '/src/Domain/Shared';
        $targetDomainSharedPath = $this->projectRoot . '/src/Domain/Shared';
        
        // Ensure Domain/Shared directory exists
        if (!is_dir($targetDomainSharedPath)) {
            mkdir($targetDomainSharedPath, 0755, true);
        }
        
        // Create all required subdirectories
        $domainSharedDirs = [
            'Core/Audit',
            'Core/Concerns',
            'Core/Contract',
            'Core/Security',
            'Core/ValueObject',
            'Common',
        ];
        
        foreach ($domainSharedDirs as $dir) {
            $dirPath = $targetDomainSharedPath . '/' . $dir;
            if (!is_dir($dirPath)) {
                mkdir($dirPath, 0755, true);
                echo "✅ Created directory: src/Domain/Shared/{$dir}\n";
            }
        }
        
        // Copy Domain Shared classes from vendor if available
        if (is_dir($vendorDomainSharedPath)) {
            $this->copyDirectory($vendorDomainSharedPath, $targetDomainSharedPath);
            echo "✅ Copied Domain Shared classes from vendor\n";
        } else {
            // Fallback: copy from current location (for testing in boilerplate)
            $currentDomainSharedPath = $this->projectRoot . '/src/Domain/Shared';
            if (is_dir($currentDomainSharedPath)) {
                $this->copyDirectory($currentDomainSharedPath, $targetDomainSharedPath);
                echo "✅ Copied existing Domain Shared classes\n";
            }
        }
    }

    private function copyApplicationSharedClasses(): void
    {
        // In actual vendor package usage, copy from vendor to project
        $vendorApplicationSharedPath = $this->vendorPath . '/src/Application/Shared';
        $targetApplicationSharedPath = $this->projectRoot . '/src/Application/Shared';
        
        // Ensure Application/Shared directory exists
        if (!is_dir($targetApplicationSharedPath)) {
            mkdir($targetApplicationSharedPath, 0755, true);
        }
        
        // Create all required subdirectories
        $applicationSharedDirs = [
            'Core/Factory',
            'Common',
        ];
        
        foreach ($applicationSharedDirs as $dir) {
            $dirPath = $targetApplicationSharedPath . '/' . $dir;
            if (!is_dir($dirPath)) {
                mkdir($dirPath, 0755, true);
                echo "✅ Created directory: src/Application/Shared/{$dir}\n";
            }
        }
        
        // Copy Application Shared classes from vendor if available
        if (is_dir($vendorApplicationSharedPath)) {
            $this->copyDirectory($vendorApplicationSharedPath, $targetApplicationSharedPath);
            echo "✅ Copied Application Shared classes from vendor\n";
        } else {
            // Fallback: copy from current location (for testing in boilerplate)
            $currentApplicationSharedPath = $this->projectRoot . '/src/Application/Shared';
            if (is_dir($currentApplicationSharedPath)) {
                $this->copyDirectory($currentApplicationSharedPath, $targetApplicationSharedPath);
                echo "✅ Copied existing Application Shared classes\n";
            }
        }
    }

    private function copyApiSharedClasses(): void
    {
        // In actual vendor package usage, copy from vendor to project
        $vendorApiSharedPath = $this->vendorPath . '/src/Api/Shared';
        $targetApiSharedPath = $this->projectRoot . '/src/Api/Shared';
        
        // Ensure Api/Shared directory exists
        if (!is_dir($targetApiSharedPath)) {
            mkdir($targetApiSharedPath, 0755, true);
        }
        
        // Create all required subdirectories
        $apiSharedDirs = [
            'Presenter'
        ];
        
        foreach ($apiSharedDirs as $dir) {
            $dirPath = $targetApiSharedPath . '/' . $dir;
            if (!is_dir($dirPath)) {
                mkdir($dirPath, 0755, true);
                echo "✅ Created directory: src/Api/Shared/{$dir}\n";
            }
        }
        
        // Copy API Shared classes from vendor if available
        if (is_dir($vendorApiSharedPath)) {
            $this->copyDirectory($vendorApiSharedPath, $targetApiSharedPath);
            echo "✅ Copied API Shared classes from vendor\n";
        } else {
            // Fallback: copy from current location (for testing in boilerplate)
            $currentApiSharedPath = $this->projectRoot . '/src/Api/Shared';
            if (is_dir($currentApiSharedPath)) {
                $this->copyDirectory($currentApiSharedPath, $targetApiSharedPath);
                echo "✅ Copied existing API Shared classes\n";
            }
        }
        
        // Specifically copy the root level files if they exist
        $specificFiles = [
            'ExceptionResponderFactory.php',
            'ResponseFactory.php'
        ];
        
        foreach ($specificFiles as $file) {
            $sourceFile = $this->projectRoot . '/src/Api/Shared/' . $file;
            $targetFile = $targetApiSharedPath . '/' . $file;
            
            if (file_exists($sourceFile)) {
                $content = file_get_contents($sourceFile);
                file_put_contents($targetFile, $content);
                echo "✅ Copied file: src/Api/Shared/{$file}\n";
            }
        }
    }

    private function copyConfigFiles(): void
    {
        // In actual vendor package usage, copy from vendor to project
        $vendorConfigPath = $this->vendorPath . '/config';
        $targetConfigPath = $this->projectRoot . '/config';
        
        // Ensure config directory exists
        if (!is_dir($targetConfigPath)) {
            mkdir($targetConfigPath, 0755, true);
        }
        
        // Copy all common/di files at once
        $commonDiSource = $vendorConfigPath . '/common/di';
        $commonDiFallback = $this->projectRoot . '/config/common/di';
        $commonDiTarget = $targetConfigPath . '/common/di';

        if (is_dir($commonDiSource)) {
            $this->copyDirectory($commonDiSource, $commonDiTarget);
            echo "✅ Copied config/common/di directory from vendor\n";
        } elseif (is_dir($commonDiFallback)) {
            $this->copyDirectory($commonDiFallback, $commonDiTarget);
            echo "✅ Copied config/common/di directory from current project\n";
        }

        // Copy all web/di files at once
        $webDiSource = $vendorConfigPath . '/web/di';
        $webDiFallback = $this->projectRoot . '/config/web/di';
        $webDiTarget = $targetConfigPath . '/web/di';

        if (is_dir($webDiSource)) {
            $this->copyDirectory($webDiSource, $webDiTarget);
            echo "✅ Copied config/web/di directory from vendor\n";
        } elseif (is_dir($webDiFallback)) {
            $this->copyDirectory($webDiFallback, $webDiTarget);
            echo "✅ Copied config/web/di directory from current project\n";
        }

        // Copy all console config files at once
        $consoleSource = $vendorConfigPath . '/console';
        $consoleFallback = $this->projectRoot . '/config/console';
        $consoleTarget = $targetConfigPath . '/console';

        if (is_dir($consoleSource)) {
            $this->copyDirectory($consoleSource, $consoleTarget);
            echo "✅ Copied config/console directory from vendor\n";
        } elseif (is_dir($consoleFallback)) {
            $this->copyDirectory($consoleFallback, $consoleTarget);
            echo "✅ Copied config/console directory from current project\n";
        }
    }

    private function copyMessageFiles(): void
    {
        // In actual vendor package usage, copy from vendor to project
        $vendorMessagesPath = $this->vendorPath . '/resources/messages';
        $targetMessagesPath = $this->projectRoot . '/resources/messages';

        // Ensure resources/messages directory exists
        if (!is_dir($targetMessagesPath)) {
            mkdir($targetMessagesPath, 0755, true);
        }

        $sourcePath = is_dir($vendorMessagesPath) ? $vendorMessagesPath : $targetMessagesPath;

        if (!is_dir($sourcePath)) {
            return;
        }

        $languages = new DirectoryIterator($sourcePath);
        foreach ($languages as $langInfo) {
            if ($langInfo->isDot() || !$langInfo->isDir()) {
                continue;
            }

            $lang = $langInfo->getFilename();
            $langDir = $targetMessagesPath . '/' . $lang;
            if (!is_dir($langDir)) {
                mkdir($langDir, 0755, true);
                echo "✅ Created directory: resources/messages/{$lang}\n";
            }

            $files = new DirectoryIterator($sourcePath . '/' . $lang);
            foreach ($files as $fileInfo) {
                if ($fileInfo->isDot() || $fileInfo->isDir()) {
                    continue;
                }

                $file = $fileInfo->getFilename();

                // app.php is project-specific, do not overwrite
                if ($file === 'app.php') {
                    continue;
                }

                $sourceFile = $sourcePath . '/' . $lang . '/' . $file;
                $targetFile = $langDir . '/' . $file;

                if ($sourceFile === $targetFile) {
                    continue;
                }

                if (file_exists($sourceFile)) {
                    $content = file_get_contents($sourceFile);
                    file_put_contents($targetFile, $content);
                    echo "✅ Copied message file: resources/messages/{$lang}/{$file}\n";
                }
            }
        }
    }

    private function copyApiFiles(): void
    {
        // In actual vendor package usage, copy from vendor to project
        $vendorApiPath = $this->vendorPath . '/src/Api';
        $vendorRootPath = $this->vendorPath . '/src';
        $targetApiPath = $this->projectRoot . '/src/Api';
        $targetRootPath = $this->projectRoot . '/src';
        
        // Ensure directories exist
        if (!is_dir($targetApiPath)) {
            mkdir($targetApiPath, 0755, true);
        }
        if (!is_dir($targetRootPath)) {
            mkdir($targetRootPath, 0755, true);
        }
        
        // Copy API files
        $apiFiles = [
            'IndexAction.php'
        ];
        
        foreach ($apiFiles as $file) {
            $sourceFile = $vendorApiPath . '/' . $file;
            $targetFile = $targetApiPath . '/' . $file;
            
            if (file_exists($sourceFile)) {
                $content = file_get_contents($sourceFile);
                file_put_contents($targetFile, $content);
                echo "✅ Copied API file: src/Api/{$file}\n";
            } else {
                // Fallback: copy from current location (for testing in boilerplate)
                $currentSourceFile = $this->projectRoot . '/src/Api/' . $file;
                if (file_exists($currentSourceFile)) {
                    $content = file_get_contents($currentSourceFile);
                    file_put_contents($targetFile, $content);
                    echo "✅ Copied existing API file: src/Api/{$file}\n";
                }
            }
        }
        
        // Copy autoload.php from src/ (not src/Api/)
        $autoloadSource = $vendorRootPath . '/autoload.php';
        $autoloadTarget = $targetRootPath . '/autoload.php';
        
        if (file_exists($autoloadSource)) {
            $content = file_get_contents($autoloadSource);
            file_put_contents($autoloadTarget, $content);
            echo "✅ Copied autoload file: src/autoload.php\n";
        } else {
            // Fallback: copy from current location (for testing in boilerplate)
            $currentAutoloadSource = $this->projectRoot . '/src/autoload.php';
            if (file_exists($currentAutoloadSource)) {
                $content = file_get_contents($currentAutoloadSource);
                file_put_contents($autoloadTarget, $content);
                echo "✅ Copied existing autoload file: src/autoload.php\n";
            }
        }
    }

    private function createEmptyDirectories(): void
    {
        echo "📁 Creating empty directories...\n";
        
        // Define directories to create
        $directories = [
            'src/Migration',
            'src/Seeder',
            'src/Seeder/Fixtures',
            'src/Seeder/Faker',
        ];
        
        foreach ($directories as $directory) {
            $dirPath = $this->projectRoot . '/' . $directory;
            if (!is_dir($dirPath)) {
                mkdir($dirPath, 0755, true);
                echo "✅ Created directory: {$directory}\n";
            } else {
                echo "📁 Directory already exists: {$directory}\n";
            }
        }
        
        // Copy skeleton-owned console commands
        $this->copyConsoleCommands();
        
        // Create .gitkeep files to preserve empty directories in git
        $this->createGitKeepFile($this->projectRoot . '/src/Migration/.gitkeep');
        $this->createGitKeepFile($this->projectRoot . '/src/Seeder/.gitkeep');
    }
    
    private function createGitKeepFile(string $path): void
    {
        if (!file_exists($path)) {
            file_put_contents($path, "# This file ensures the directory is tracked by git\n");
            echo "✅ Created .gitkeep file: " . basename(dirname($path)) . "\n";
        }
    }

    private function copyQualityScript(): void
    {
        // In actual vendor package usage, copy from vendor to project
        $vendorQualityScript = $this->vendorPath . '/quality';
        $targetQualityScript = $this->projectRoot . '/quality';
        
        if (file_exists($vendorQualityScript)) {
            $content = file_get_contents($vendorQualityScript);
            file_put_contents($targetQualityScript, $content);
            
            // Make it executable (on Unix systems)
            chmod($targetQualityScript, 0755);
            
            echo "✅ Copied Quality Assurance script from vendor\n";
        } else {
            // Fallback: copy from current location (for testing in boilerplate)
            $currentQualityScript = $this->projectRoot . '/quality';
            if (file_exists($currentQualityScript)) {
                $content = file_get_contents($currentQualityScript);
                file_put_contents($targetQualityScript, $content);
                
                // Make it executable (on Unix systems)
                chmod($targetQualityScript, 0755);
                
                echo "✅ Copied existing Quality Assurance script\n";
            }
        }
    }

    private function updateComposerJson(): void
    {
        $composerFile = $this->projectRoot . '/composer.json';
        
        if (!file_exists($composerFile)) {
            echo "❌ composer.json not found\n";
            return;
        }

        $content = file_get_contents($composerFile);
        $data = json_decode($content, true);
        
        if ($data === null) {
            echo "❌ Invalid composer.json\n";
            return;
        }

        // Required packages to add
        $requiredPackages = [
            "firebase/php-jwt" => "^7.0.2",
            "mongodb/mongodb" => "^2.1",
            "psr/clock" => "^1.0",
            "vlucas/phpdotenv" => "^5.6.3",
            "yiisoft/access" => "2.0",
            "yiisoft/cache" => "^3.2",
            "yiisoft/cache-file" => "^3.2",
            "yiisoft/db" => "^2.0",
            "yiisoft/db-migration" => "^2.0.1",
            "yiisoft/db-mysql" => "^2.0",
            "yiisoft/db-pgsql" => "^2.0",
            "yiisoft/router" => "^4.0.2",
            "yiisoft/router-fastroute" => "^4.0.3",
            "yiisoft/security" => "^1.2",
            "yiisoft/translator" => "^3.2.1",
            "yiisoft/translator-message-php" => "^1.1.2"
        ];

        // Merge required packages into existing require section
        if (!isset($data['require'])) {
            $data['require'] = [];
        }
        
        $data['require'] = array_merge($data['require'], $requiredPackages);
        
        // Add development packages
        if (!isset($data['require-dev'])) {
            $data['require-dev'] = [];
        }
        
        $devPackages = [
            "nelmio/alice" => "^3.16.1",
        ];
        
        $data['require-dev'] = array_merge($data['require-dev'], $devPackages);
        
        // Sort packages alphabetically
        ksort($data['require']);
        ksort($data['require-dev']);
        
        $newContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        file_put_contents($composerFile, $newContent);
        
        echo "✅ Updated composer.json with required packages\n";
        echo "💡 Run 'composer update' to install the new packages\n";
    }

    private function copyDirectory(string $source, string $target): void
    {
        if (!is_dir($source)) {
            return;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $sourcePath = $file->getPathname();
            $relativePath = str_replace($source, '', $sourcePath);
            $targetPath = $target . $relativePath;

            if ($file->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                // Skip if target file already exists and it's ValidationContext.php
                if (file_exists($targetPath) && str_contains($targetPath, 'ValidationContext.php')) {
                    echo "⏭️  Skipped existing ValidationContext: " . str_replace($this->projectRoot . '/', '', $targetPath) . "\n";
                    continue;
                }
                
                // Copy file as-is without replacements
                $content = file_get_contents($sourcePath);
                
                // Ensure target directory exists
                $targetDir = dirname($targetPath);
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                
                file_put_contents($targetPath, $content);
                
                // Copy permissions
                $permissions = fileperms($sourcePath);
                if ($permissions !== false) {
                    chmod($targetPath, $permissions);
                }
            }
        }
    }
    
    private function copyConsoleCommands(): void
    {
        echo "🖥️  Copying console commands...\n";

        $sourcePath = $this->vendorPath . '/src/Console';
        $targetPath = $this->projectRoot . '/src/Console';

        if (is_dir($sourcePath)) {
            $this->copyDirectory($sourcePath, $targetPath);
            echo "✅ Copied console commands from vendor\n";
        }
    }
}

// Run installer
try {
    $installer = new SkeletonInstaller();
    $installer->install();
} catch (Exception $e) {
    echo "❌ Installation failed: " . $e->getMessage() . "\n";
    exit(1);
}
