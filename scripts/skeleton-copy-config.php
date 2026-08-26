<?php
/**
 * Copy example files to target location
 * Based on Yii2 skeleton copy examples approach
 */

class SkeletonConfigCopier
{
    private string $projectRoot;
    private string $vendorPath;
    
    public function __construct()
    {
        $this->projectRoot = dirname(__DIR__);
        $this->vendorPath = $this->projectRoot . '/vendor/rahmatsyaparudin/yii3-api-boilerplate';
    }
    
    public function copy(): void
    {
        echo "🚀 Copying config files from skeleton...\n";
        
        // Files and directories to copy
        $itemsToCopy = [
            // Files
            '.env.example' => '.env',

            // Config files
            'config/common/access.php' => 'config/common/access.php',
            'config/common/aliases.php' => 'config/common/aliases.php',
            'config/common/application.php' => 'config/common/application.php',
            'config/common/routes.php' => 'config/common/routes.php',
            'config/common/repository.php' => 'config/common/repository.php',
            'config/common/service.php' => 'config/common/service.php',
            'config/common/translator.php' => 'config/common/translator.php',
            'config/common/di/application.php' => 'config/common/di/application.php',
            'config/console/commands.php' => 'config/console/commands.php',
        ];
        
        $flagFile = $this->projectRoot . '/.skeleton_config_copied';
        
        if (file_exists($flagFile)) {
            echo "⚠️  Config files were already copied. Skipping...\n";
            echo "💡 To force re-copy, remove: {$flagFile}\n";
            return;
        }
        
        foreach ($itemsToCopy as $source => $target) {
            $this->copyItem($source, $target);
        }
        
        // Create flag file
        file_put_contents($flagFile, date(DATE_ATOM));
        
        echo "✅ Config files copied successfully!\n";
        echo "\n🎯 Next steps:\n";
        echo "Copy example files from skeleton\n";
        echo "Run: composer skeleton-copy-examples\n";
    }
    
    private function copyItem(string $source, string $target): void
    {
        $sourcePath = $this->vendorPath . '/' . $source;
        $targetPath = $this->projectRoot . '/' . $target;
        
        // Check if source exists in vendor
        if (!file_exists($sourcePath)) {
            // Fallback to current location (for testing in boilerplate)
            $sourcePath = $this->projectRoot . '/' . $source;
            if (!file_exists($sourcePath)) {
                echo "⚠️  Warning: {$source} not found\n";
                return;
            }
        }
        
        // Ensure target directory exists
        $targetDir = dirname($targetPath);
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
            echo "📁 Created directory: " . str_replace($this->projectRoot . '/', '', $targetDir) . "\n";
        }
        
        if (is_dir($sourcePath)) {
            // Copy directory recursively
            $this->copyDirectory($sourcePath, $targetPath);
            echo "📁 Copied directory: {$source} → " . str_replace($this->projectRoot . '/', '', $targetPath) . "\n";
        } else {
            // Copy single file
            $content = file_get_contents($sourcePath);
            file_put_contents($targetPath, $content);
            echo "📄 Copied file: {$source} → " . str_replace($this->projectRoot . '/', '', $targetPath) . "\n";
        }
    }
    
    private function copyDirectory(string $source, string $target): void
    {
        if (!is_dir($source)) {
            return;
        }
        
        // Ensure target directory exists
        if (!is_dir($target)) {
            mkdir($target, 0755, true);
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
                file_put_contents($targetPath, $content);
                
                // Copy permissions
                $permissions = fileperms($sourcePath);
                if ($permissions !== false) {
                    chmod($targetPath, $permissions);
                }
            }
        }
    }
}

// Run the copier
try {
    $copier = new SkeletonConfigCopier();
    $copier->copy();
} catch (Exception $e) {
    echo "❌ Copy failed: " . $e->getMessage() . "\n";
    exit(1);
}
