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
    private array $replacements;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
        $this->projectRoot = dirname(__DIR__);
        $this->replacements = $this->getReplacements();
    }

    public function install(): void
    {
        echo "🚀 Installing Yii3 API skeleton...\n";
        
        // Step 1: Update composer.json for new project
        $this->updateComposerJson();
        
        // Step 2: Apply string replacements to all files
        $this->applyReplacements();
        
        // Step 3: Create .env from example
        $this->setupEnvironment();
        
        // Step 4: Clean up boilerplate-specific files
        $this->cleanup();
        
        echo "✅ Skeleton installation completed!\n";
        echo "\n🎯 Next steps:\n";
        echo "1. Configure .env file with your database settings\n";
        echo "2. Run: composer install\n";
        echo "3. Run: php yii migrate\n";
        echo "4. Run: php yii serve\n";
        echo "\n🌐 Your API will be available at: http://localhost:8080\n";
    }

    private function getReplacements(): array
    {
        $projectName = basename($this->projectRoot);
        $projectNameCamel = $this->toCamelCase($projectName);
        $projectNameUpper = strtoupper(str_replace('-', '_', $projectName));
        
        return [
            'yii3-api' => $projectName,
            'Yii3Api' => $projectNameCamel,
            'YII3_API' => $projectNameUpper,
            'rahmatsyaparudin/yii3-api' => 'vendor/' . $projectName,
            'rahmatsyaparudin/yii3-api-boilerplate' => 'vendor/' . $projectName,
            'Example' => 'Entity', // Default entity name
            'example' => 'entity',
        ];
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

        // Update package info for new project
        $data['name'] = 'vendor/' . basename($this->projectRoot);
        $data['description'] = 'API project based on Yii3 framework';
        $data['type'] = 'project';
        
        // Remove boilerplate-specific metadata
        unset($data['keywords'], $data['homepage'], $data['support'], $data['funding']);
        
        // Update scripts to remove install-skeleton
        unset($data['scripts']['install-skeleton']);
        
        // Add new useful scripts
        $data['scripts']['start'] = 'php yii serve';
        $data['scripts']['migrate'] = 'php yii migrate';
        $data['scripts']['generate'] = 'php yii simple-generate';
        
        $newContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($composerFile, $newContent);
        
        echo "✅ Updated composer.json\n";
    }

    private function applyReplacements(): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->projectRoot, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }

            $filePath = $file->getPathname();
            
            // Skip certain files
            if ($this->shouldSkipFile($filePath)) {
                continue;
            }

            $this->processFile($filePath);
        }
        
        echo "✅ Applied string replacements\n";
    }

    private function processFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $originalContent = $content;
        
        // Apply replacements
        foreach ($this->replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }
        
        // Only write if content changed
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
        }
    }

    private function shouldSkipFile(string $filePath): bool
    {
        $skipPatterns = [
            'vendor/',
            '.git/',
            'node_modules/',
            'runtime/',
            'public/assets/',
            '*.log',
            'composer.lock',
            '.env',
            '.env.local',
        ];

        foreach ($skipPatterns as $pattern) {
            if (str_contains($filePath, $pattern)) {
                return true;
            }
        }

        // Skip binary files
        $binaryExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'zip', 'tar', 'gz'];
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if (in_array($extension, $binaryExtensions)) {
            return true;
        }

        return false;
    }

    private function setupEnvironment(): void
    {
        $envExample = $this->projectRoot . '/.env.example';
        $envFile = $this->projectRoot . '/.env';
        
        if (file_exists($envExample) && !file_exists($envFile)) {
            copy($envExample, $envFile);
            echo "✅ Created .env from .env.example\n";
        }
    }

    private function cleanup(): void
    {
        // Remove boilerplate-specific files
        $filesToRemove = [
            'scripts/install-skeleton.php',
            'README-BOILERPLATE.md',
            'DEPLOYMENT_GUIDE.md',
            'setup-composer-template.sh',
        ];

        foreach ($filesToRemove as $file) {
            $filePath = $this->projectRoot . '/' . $file;
            if (file_exists($filePath)) {
                unlink($filePath);
                echo "✅ Removed: {$file}\n";
            }
        }

        // Remove empty scripts directory if it exists
        $scriptsDir = $this->projectRoot . '/scripts';
        if (is_dir($scriptsDir) && $this->isEmptyDirectory($scriptsDir)) {
            rmdir($scriptsDir);
            echo "✅ Removed empty scripts directory\n";
        }
    }

    private function isEmptyDirectory(string $dir): bool
    {
        $files = scandir($dir);
        return count($files) <= 2; // . and ..
    }

    private function toCamelCase(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
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
