<?php
/**
 * Copy example files to target location
 * Based on Yii2 skeleton copy examples approach
 */

class SkeletonExamplesCopier
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
        echo "🚀 Copying example files from skeleton...\n";
        
        // Files and directories to copy
        $itemsToCopy = [
            '.env.example' => '.env.example',

            // Message files
            'resources/messages/en/app.php' => 'resources/messages/en/app.php',
            'resources/messages/id/app.php' => 'resources/messages/id/app.php',
            
            // Config files
            'config/common/params.php' => 'config/common/params.php',
            'config/common/di/infrastructure.php' => 'config/common/di/infrastructure.php',
            'config/common/di/optimistic-lock.php' => 'config/common/di/optimistic-lock.php',
            'config/common/di/validator.php' => 'config/common/di/validator.php',
            'config/console/commands.php' => 'config/console/commands.php',
            'config/console/params.php' => 'config/console/params.php',
            
            // Directories (recursive copy)
            'src/Api/V1/Example' => 'src/Api/V1/Example',
            'src/Application/Example' => 'src/Application/Example',
            'src/Domain/Example' => 'src/Domain/Example',
            'src/Infrastructure/Persistence/Example' => 'src/Infrastructure/Persistence/Example',
            'src/Migration' => 'src/Migration',

            'src/Seeder/Fixtures/example.yaml' => 'src/Seeder/Fixtures/example.yaml',
            'src/Seeder/Faker/SeedDataPoolFaker.php' => 'src/Seeder/Faker/SeedDataPoolFaker.php',
            'src/Seeder/SeedExampleData.php' => 'src/Seeder/SeedExampleData.php',
        ];

        $skipFlagFile = true;
        
        $flagFile = $this->projectRoot . '/.skeleton_examples_copied';
        
        if (!$skipFlagFile && file_exists($flagFile)) {
            echo "⚠️  Example files were already copied. Skipping...\n";
            echo "💡 To force re-copy, remove: {$flagFile}\n";
            return;
        }
        
        foreach ($itemsToCopy as $source => $target) {
            $this->copyItem($source, $target);
        }
        
        // Create flag file
        if (!$skipFlagFile) {
            file_put_contents($flagFile, date(DATE_ATOM));
        }
        
        echo "✅ Example files copied successfully!\n";
        echo "\n🎯 Next steps:\n";
        echo "1. Configure your .env file with your database settings\n";
        echo "2. Run: composer install\n";
        echo "3. Run: ./yii migrate:up\n";
        echo "4. Run: ./yii seed --module=example (development only)\n";
        echo "5. Run: ./yii serve\n";
        echo "\n🌐 Your API will be available at: http://localhost:8080\n";
        echo "\n📚 Documentation:\n";
        echo "- Architecture Guide: docs/architecture-guide.md\n";
        echo "- Quality Guide: docs/quality-guide.md\n";
        echo "- Setup Guide: docs/setup-guide.md\n";
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
        
        // Skip if target file already exists and it's a messages file
        if (file_exists($targetPath) && str_contains($target, 'resources/messages/')) {
            echo "⏭️  Skipped existing messages file: " . str_replace($this->projectRoot . '/', '', $targetPath) . "\n";
            return;
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
    $copier = new SkeletonExamplesCopier();
    $copier->copy();
} catch (Exception $e) {
    echo "❌ Copy failed: " . $e->getMessage() . "\n";
    exit(1);
}
