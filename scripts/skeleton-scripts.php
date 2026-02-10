#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Composer Script: Copy Scripts from Boilerplate
 * 
 * This script copies the scripts folder from the boilerplate package
 * to the project root, ensuring we have the latest script versions
 * before running skeleton-update
 */

final class SkeletonScriptsCopier
{
    private string $projectRoot;
    private string $vendorPath;

    public function __construct()
    {
        $this->projectRoot = dirname(__DIR__, 1);
        $this->vendorPath = $this->projectRoot . '/vendor';
    }

    public function copy(): void
    {
        echo "🔧 Copying scripts from boilerplate...\n";

        $vendorScriptsPath = $this->vendorPath . '/rahmatsyaparudin/yii3-api-boilerplate/scripts';
        $targetScriptsPath = $this->projectRoot . '/scripts';

        // Check if vendor scripts exists
        if (!is_dir($vendorScriptsPath)) {
            echo "⚠️  Boilerplate scripts not found in vendor. Skipping...\n";
            return;
        }

        // Create scripts directory if it doesn't exist
        if (!is_dir($targetScriptsPath)) {
            mkdir($targetScriptsPath, 0755, true);
            echo "📁 Created scripts directory\n";
        }

        // Copy all script files from vendor
        $this->copyDirectory($vendorScriptsPath, $targetScriptsPath);
        
        echo "✅ Scripts copied successfully!\n";
        echo "\n🎯 Next steps:\n";
        echo "💡 Run 'composer skeleton-update' to update your project\n";
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
                // Copy file as-is
                $content = file_get_contents($sourcePath);
                
                // Ensure target directory exists
                $targetDir = dirname($targetPath);
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                
                file_put_contents($targetPath, $content);
                
                // Copy permissions
                $permissions = fileperms($sourcePath);
                chmod($targetPath, $permissions);
                
                echo "📄 Copied script: " . basename($sourcePath) . "\n";
            }
        }
    }
}

// Run the copier
$copier = new SkeletonScriptsCopier();
$copier->copy();
