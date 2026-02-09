#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Module Generator Script
 * 
 * This script generates new API modules by copying from the Example template.
 * It copies all directories and files from Example folders and renames them
 * to use the new module name.
 * 
 * Usage:
 * php scripts/generate-module.php --module=<ModuleName>
 * 
 * Examples:
 * php scripts/generate-module.php --module=Product
 * php scripts/generate-module.php --module=Order
 * php scripts/generate-module.php --module=User
 */

/**
 * Module Generator Class
 * 
 * Handles the generation of new API modules by copying from Example template.
 */
final class ModuleGenerator
{
    private string $projectRoot;
    private string $moduleName;
    private string $moduleLower;
    private string $moduleUpper;
    private string $tableName;

    /**
     * Module Generator constructor
     * 
     * @param string $projectRoot Root directory of the project
     * @param string $moduleName Name of the module to generate
     * @param string $tableName Custom table name (optional, defaults to lowercase module name)
     */
    public function __construct(string $projectRoot, string $moduleName, string $tableName = null)
    {
        $this->projectRoot = $projectRoot;
        $this->moduleName = ucfirst($moduleName);
        $this->moduleLower = strtolower($moduleName);
        $this->moduleUpper = strtoupper($moduleName);
        $this->tableName = $tableName ?? $this->moduleLower;
    }

    /**
     * Generate the complete module structure
     */
    public function generate(): void
    {
        echo "🚀 Generating module: {$this->moduleName}\n\n";
        
        $createdFiles = [];
        $createdDirectories = [];
        
        // Define source and target directories
        $directories = [
            'src/Api/V1/Example' => "src/Api/V1/{$this->moduleName}",
            'src/Application/Example' => "src/Application/{$this->moduleName}",
            'src/Domain/Example' => "src/Domain/{$this->moduleName}",
            'src/Infrastructure/Persistence/Example' => "src/Infrastructure/Persistence/{$this->moduleName}",
        ];
        
        // Copy each directory
        foreach ($directories as $source => $target) {
            $this->copyDirectory($source, $target, $createdDirectories, $createdFiles);
        }
        
        // Generate migration file
        $this->generateMigration($createdFiles);
        
        // Generate seed file
        $this->generateSeed($createdFiles);
        
        // Generate fixture file
        $this->generateFixture($createdFiles);
        
        // Update configuration files
        $this->updateConfigurations($createdFiles);
        
        // Output summary
        $this->outputSummary($createdDirectories, $createdFiles);
        
        echo "\n✅ Module '{$this->moduleName}' generated successfully!\n";
        echo "📁 Check the generated files and customize as needed.\n";
    }

    /**
     * Update configuration files
     */
    private function updateConfigurations(array &$createdFiles): void
    {
        // Update repository DI
        $this->updateRepositoryDi($createdFiles);
        
        // Update access configuration
        $this->updateAccessConfig($createdFiles);
        
        // Update routes configuration
        $this->updateRoutesConfig($createdFiles);
    }

    /**
     * Update repository DI configuration
     */
    private function updateRepositoryDi(array &$createdFiles): void
    {
        $configFile = 'config/common/di/repository.php';
        
        if (!file_exists($configFile)) {
            echo "❌ Repository DI config not found: {$configFile}\n";
            return;
        }
        
        $content = file_get_contents($configFile);
        
        // Check if module already exists
        if (str_contains($content, "{$this->moduleName}RepositoryInterface::class")) {
            echo "📄 Repository DI already exists for {$this->moduleName}\n";
            return;
        }
        
        // Add Domain use statement after Example line
        $content = str_replace(
            "use App\\Domain\\Example\\Repository\\ExampleRepositoryInterface;",
            "use App\\Domain\\Example\\Repository\\ExampleRepositoryInterface;\nuse App\\Domain\\{$this->moduleName}\\Repository\\{$this->moduleName}RepositoryInterface;",
            $content
        );
        
        // Add Infrastructure use statement after Example line
        $content = str_replace(
            "use App\\Infrastructure\\Persistence\\Example\\ExampleRepository;",
            "use App\\Infrastructure\\Persistence\\Example\\ExampleRepository;\nuse App\\Infrastructure\\Persistence\\{$this->moduleName}\\{$this->moduleName}Repository;",
            $content
        );
        
        // Add LockVersionConfig use statement after CurrentUser line
        $content = str_replace(
            "use App\\Infrastructure\\Security\\CurrentUser;",
            "use App\\Infrastructure\\Security\\CurrentUser;\nuse App\\Shared\\ValueObject\\LockVersionConfig;",
            $content
        );
        
        // Add repository DI configuration
        $newDiConfig = "    {$this->moduleName}RepositoryInterface::class => [
        'class' => {$this->moduleName}Repository::class,
        'setLockVersionConfig()' => [Reference::to(LockVersionConfig::class)],
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
        '__construct()' => [
            'params' => \$params['app/optimisticLock'] ?? [],
        ],
    ],";
        
        // Add before closing bracket
        $content = preg_replace('/(\];\s*$)/', $newDiConfig . "\n];", $content);
        
        file_put_contents($configFile, $content);
        $createdFiles[] = $configFile;
        echo "⚙️ Updated repository DI: {$configFile}\n";
    }

    /**
     * Update seed DI configuration
     */
    private function updateSeedDi(array &$createdFiles): void
    {
        $configFile = 'config/common/di/seed.php';
        
        if (!file_exists($configFile)) {
            echo "❌ Seed DI config not found: {$configFile}\n";
            return;
        }
        
        $content = file_get_contents($configFile);
        
        // Check if module already exists
        if (str_contains($content, "Seed{$this->moduleName}Data")) {
            echo "📄 Seed DI already exists for {$this->moduleName}\n";
            return;
        }
        
        // Generate timestamp for seed file
        $timestamp = date('YmdHis', strtotime('+1 hour'));
        
        // Add use statement after SeedExampleCommand
        $content = str_replace(
            "use App\\Console\\SeedExampleCommand;",
            "use App\\Console\\SeedExampleCommand;\nuse App\\Seed\\M{$timestamp}Seed{$this->moduleName}Data;",
            $content
        );
        
        // Add seed DI configuration
        $newDiConfig = "    Seed{$this->moduleName}Data::class => [
        'class' => Seed{$this->moduleName}Data::class,
        '__construct()' => [
            Reference::to(ClockInterface::class),
        ],
    ],";
        
        // Add before closing bracket
        $content = preg_replace('/(\];\s*$)/', $newDiConfig . "\n];", $content);
        
        file_put_contents($configFile, $content);
        $createdFiles[] = $configFile;
        echo "⚙️ Updated seed DI: {$configFile}\n";
    }

    /**
     * Update access configuration
     */
    private function updateAccessConfig(array &$createdFiles): void
    {
        $configFile = 'config/common/access.php';
        
        if (!file_exists($configFile)) {
            echo "❌ Access config not found: {$configFile}\n";
            return;
        }
        
        $content = file_get_contents($configFile);
        
        // Check if module already exists
        if (str_contains($content, "'{$this->moduleLower}.index'")) {
            echo "📄 Access config already exists for {$this->moduleName}\n";
            return;
        }
        
        // Add access rules with proper newline
        $accessRules = "\n    // {$this->moduleName} Access Rules
    '{$this->moduleLower}.index' => true,
    '{$this->moduleLower}.data' => true,
    '{$this->moduleLower}.view' => \$isKasir,
    '{$this->moduleLower}.create' => \$isKasir,
    '{$this->moduleLower}.update' => \$isKasir,
    '{$this->moduleLower}.delete' => \$isKasir,
    '{$this->moduleLower}.restore' => \$isSuperAdmin,";
        
        // Add before closing bracket
        $content = preg_replace('/(\];\s*$)/', $accessRules . "\n];", $content);
        
        file_put_contents($configFile, $content);
        $createdFiles[] = $configFile;
        echo "⚙️ Updated access config: {$configFile}\n";
    }

    /**
     * Update routes configuration
     */
    private function updateRoutesConfig(array &$createdFiles): void
    {
        $configFile = 'config/common/routes.php';
        
        if (!file_exists($configFile)) {
            echo "❌ Routes config not found: {$configFile}\n";
            return;
        }
        
        $content = file_get_contents($configFile);
        
        // Check if module routes already exist
        if (str_contains($content, "// {$this->moduleName} Routes")) {
            echo "📄 Routes config already exists for {$this->moduleName}\n";
            return;
        }
        
        // Add use statement for all action classes
        $useStatements = "use App\\Api\\V1\\{$this->moduleName}\\Action as {$this->moduleName}V1;";
        
        // Add use statements after existing use statements
        // More reliable approach using strpos and manual insertion
        $usePattern = "use App\\Api\\V1\\Example\\Action as ExampleV1;";
        $usePos = strpos($content, $usePattern);
        
        if ($usePos !== false) {
            // Insert after Example use statement
            $insertPos = $usePos + strlen($usePattern);
            $content = substr_replace($content, "\n" . $useStatements, $insertPos, 0);
        } else {
            // Fallback: add after Api Layer comment
            $apiLayerPattern = "// Api Layer";
            $apiLayerPos = strpos($content, $apiLayerPattern);
            if ($apiLayerPos !== false) {
                $insertPos = $apiLayerPos + strlen($apiLayerPattern);
                $content = substr_replace($content, "\n" . $useStatements, $insertPos, 0);
            }
        }
        
        // Add routes with proper formatting
        $routes = "\n\n            // {$this->moduleName} Routes
            Route::get('/{$this->moduleLower}')
                ->action({$this->moduleName}V1\\{$this->moduleName}DataAction::class)
                ->name('v1/{$this->moduleLower}/index')
                ->defaults(['permission' => '{$this->moduleLower}.index']),
            Route::post('/{$this->moduleLower}/data')
                ->action({$this->moduleName}V1\\{$this->moduleName}DataAction::class)
                ->name('v1/{$this->moduleLower}/data')
                ->defaults(['permission' => '{$this->moduleLower}.data']),
            Route::get('/{$this->moduleLower}/{id:\\d+}')
                ->action({$this->moduleName}V1\\{$this->moduleName}ViewAction::class)
                ->name('v1/{$this->moduleLower}/view')
                ->defaults(['permission' => '{$this->moduleLower}.view']),
            Route::post('/{$this->moduleLower}/create')
                ->action({$this->moduleName}V1\\{$this->moduleName}CreateAction::class)
                ->name('v1/{$this->moduleLower}/create')
                ->defaults(['permission' => '{$this->moduleLower}.create']),
            Route::put('/{$this->moduleLower}/{id:\\d+}')
                ->action({$this->moduleName}V1\\{$this->moduleName}UpdateAction::class)
                ->name('v1/{$this->moduleLower}/update')
                ->defaults(['permission' => '{$this->moduleLower}.update']),
            Route::delete('/{$this->moduleLower}/{id:\\d+}')
                ->action({$this->moduleName}V1\\{$this->moduleName}DeleteAction::class)
                ->name('v1/{$this->moduleLower}/delete')
                ->defaults(['permission' => '{$this->moduleLower}.delete']),
            Route::post('/{$this->moduleLower}/{id:\\d+}/restore')
                ->action({$this->moduleName}V1\\{$this->moduleName}RestoreAction::class)
                ->name('v1/{$this->moduleLower}/restore')
                ->defaults(['permission' => '{$this->moduleLower}.restore']),";
        
        // Add routes using simple string replacement - find the last example route and add after it
        $lastRoutePattern = "/Route::post\('\/example\/\{id:\\d\+\}\/restore'\)[^}]+\'example\.restore\'\]\),/";
        if (preg_match($lastRoutePattern, $content)) {
            $content = preg_replace($lastRoutePattern, '$0' . $routes, $content);
        } else {
            // Fallback: add before the closing bracket
            $content = preg_replace('/(\s*\)\s*,\s*\]\s*;\s*$)/', $routes . "\n$1", $content);
        }
        
        file_put_contents($configFile, $content);
        $createdFiles[] = $configFile;
        echo "⚙️ Updated routes config: {$configFile}\n";
    }

    /**
     * Generate fixture file based on template
     */
    private function generateFixture(array &$createdFiles): void
    {
        $sourceFixture = 'src/Seeder/Fixtures/example.yaml';
        
        if (!file_exists($sourceFixture)) {
            echo "❌ Fixture template not found: {$sourceFixture}\n";
            return;
        }
        
        // Create fixtures directory if it doesn't exist
        $fixturesDir = "src/Seeder/Fixtures";
        if (!is_dir($fixturesDir)) {
            mkdir($fixturesDir, 0755, true);
            echo "📁 Created directory: {$fixturesDir}\n";
        }
        
        $targetFixture = "src/Seeder/Fixtures/{$this->moduleLower}.yaml";
        
        // Skip if file already exists
        if (file_exists($targetFixture)) {
            echo "📄 Skipped existing Fixtures: {$targetFixture}\n";
            return;
        }
        
        $content = file_get_contents($sourceFixture);
        $content = $this->replacePlaceholders($content);
        
        // Replace specific fixture patterns
        $content = str_replace("App\\Domain\\Example\\Entity\\Example", "App\\Domain\\{$this->moduleName}\\Entity\\{$this->moduleName}", $content);
        $content = str_replace("example_", "{$this->moduleLower}_", $content);
        $content = str_replace("Example Item", "{$this->moduleName} Item", $content);
        $content = str_replace("example item", "{$this->moduleLower} item", $content);
        $content = str_replace("example.yaml", "{$this->moduleLower}.yaml", $content);
        
        // Replace custom faker with built-in faker
        $content = str_replace("<seedDataPoolRandom()>", "<company()>", $content);
        
        file_put_contents($targetFixture, $content);
        $createdFiles[] = $targetFixture;
        echo "📄 Created fixture: {$targetFixture}\n";
    }

    /**
     * Generate seed file based on template
     */
    private function generateSeed(array &$createdFiles): void
    {
        $sourceSeed = 'src/Seeder/SeedExampleData.php';
        
        if (!file_exists($sourceSeed)) {
            echo "❌ Seed template not found: {$sourceSeed}\n";
            return;
        }
        
        // Check if seed for this module already exists
        $existingSeed = $this->findExistingSeed();
        if ($existingSeed) {
            echo "📄 Seed already exists: {$existingSeed}\n";
            return;
        }
        
        // Generate seed file name (without timestamp for data-seeder)
        $targetSeed = "src/Seeder/Seed{$this->moduleName}Data.php";
        
        // Skip if file already exists
        if (file_exists($targetSeed)) {
            echo "📄 Skipped existing seed: {$targetSeed}\n";
            return;
        }
        
        $content = file_get_contents($sourceSeed);
        $content = $this->replacePlaceholders($content);
        
        // Replace class name and repository interface
        $content = str_replace("SeedExampleData", "Seed{$this->moduleName}Data", $content);
        $content = str_replace("ExampleRepositoryInterface", "{$this->moduleName}RepositoryInterface", $content);
        $content = str_replace("exampleRepository", lcfirst($this->moduleName) . "Repository", $content);
        
        // Replace constructor parameters
        $content = str_replace(
            "DetailInfoFactory \$detailInfoFactory,\n        Aliases \$aliases,\n        ExampleRepositoryInterface \$exampleRepository",
            "DetailInfoFactory \$detailInfoFactory,\n        Aliases \$aliases,\n        {$this->moduleName}RepositoryInterface \$" . lcfirst($this->moduleName) . "Repository",
            $content
        );
        $content = str_replace(
            "parent::__construct(\$db, \$clock, \$detailInfoFactory, \$aliases);",
            "parent::__construct(\$db, \$clock, \$detailInfoFactory, \$aliases);",
            $content
        );
        
        // Remove getTableName, getEntityType, and isValidEntity methods (now in abstract)
        $content = preg_replace('/protected function getTableName\(\): string\s*\{[^}]*\}\s*\n\s*protected function getEntityType\(\): string\s*\{[^}]*\}\s*\n\s*protected function isValidEntity\(object \$object\): bool\s*\{[^}]*\}\s*\n/', '', $content);
        
        // Replace constants
        $content = str_replace("protected const YAML_FILE = 'example.yaml';", "protected const YAML_FILE = '{$this->moduleLower}.yaml';", $content);
        $content = str_replace("protected const TABLE_NAME = 'example';", "protected const TABLE_NAME = '{$this->tableName}';", $content);
        $content = str_replace("protected const ENTITY_CLASS = Example::class;", "protected const ENTITY_CLASS = {$this->moduleName}::class;", $content);
        
        file_put_contents($targetSeed, $content);
        $createdFiles[] = $targetSeed;
        echo "📄 Created seed: {$targetSeed}\n";
    }

    /**
     * Find existing seed for this module
     */
    private function findExistingSeed(): ?string
    {
        $seedDir = 'src/Seeder';
        if (!is_dir($seedDir)) {
            return null;
        }
        
        $files = scandir($seedDir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            // Check if file matches pattern Seed{ModuleName}Data.php
            if (preg_match("/^Seed{$this->moduleName}Data\.php$/", $file)) {
                return $seedDir . '/' . $file;
            }
        }
        
        return null;
    }

    /**
     * Generate migration file based on template
     */
    private function generateMigration(array &$createdFiles): void
    {
        $sourceMigration = 'src/Migration/M20240101000000CreateExampleTable.php';
        
        if (!file_exists($sourceMigration)) {
            echo "❌ Migration template not found: {$sourceMigration}\n";
            return;
        }
        
        // Check if migration for this module already exists
        $existingMigration = $this->findExistingMigration();
        if ($existingMigration) {
            echo "📄 Migration already exists: {$existingMigration}\n";
            return;
        }
        
        // Generate timestamp for new migration
        $timestamp = date('YmdHis');
        $targetMigration = "src/Migration/M{$timestamp}Create{$this->moduleName}Table.php";
        
        $content = file_get_contents($sourceMigration);
        
        // Replace class name with timestamp FIRST (before replacePlaceholders)
        $content = str_replace("M20240101000000CreateExampleTable", "M{$timestamp}Create{$this->moduleName}Table", $content);
        
        // Then replace other placeholders
        $content = $this->replacePlaceholders($content);
        
        // Replace table name constant
        $content = str_replace("private const TABLE_NAME = 'example';", "private const TABLE_NAME = '{$this->tableName}';", $content);
        
        file_put_contents($targetMigration, $content);
        $createdFiles[] = $targetMigration;
        echo "📄 Created migration: {$targetMigration}\n";
    }

    /**
     * Find existing migration for this module
     */
    private function findExistingMigration(): ?string
    {
        $migrationDir = 'src/Migration';
        if (!is_dir($migrationDir)) {
            return null;
        }
        
        $files = scandir($migrationDir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            // Check if file matches pattern M*Create{ModuleName}Table.php
            if (preg_match("/^M.*Create{$this->moduleName}Table\.php$/", $file)) {
                return $migrationDir . '/' . $file;
            }
        }
        
        return null;
    }

    /**
     * Copy entire directory with all files and subdirectories
     */
    private function copyDirectory(string $source, string $target, array &$createdDirectories, array &$createdFiles): void
    {
        if (!is_dir($source)) {
            echo "❌ Source directory not found: {$source}\n";
            return;
        }
        
        // Create target directory
        if (!is_dir($target)) {
            mkdir($target, 0755, true);
            $createdDirectories[] = $target;
            echo "📁 Created directory: {$target}\n";
        }
        
        // Copy all files and subdirectories
        $items = scandir($source);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $sourcePath = $source . '/' . $item;
            $targetPath = $target . '/' . $item;
            
            if (is_dir($sourcePath)) {
                // Recursively copy subdirectory
                $this->copyDirectory($sourcePath, $targetPath, $createdDirectories, $createdFiles);
            } else {
                // Copy and customize file
                $this->copyAndCustomizeFile($sourcePath, $targetPath, $createdFiles);
            }
        }
    }

    /**
     * Copy file and perform string replacements
     */
    private function copyAndCustomizeFile(string $source, string $target, array &$createdFiles): void
    {
        // Skip if file already exists
        if (file_exists($target)) {
            echo "📄 Skipped existing file: {$target}\n";
            return;
        }
        
        $content = file_get_contents($source);
        $content = $this->replacePlaceholders($content);
        
        // Rename file if it contains "Example"
        $target = $this->renameFile($target);
        
        file_put_contents($target, $content);
        $createdFiles[] = $target;
        echo "📄 Created file: {$target}\n";
    }

    /**
     * Rename file to use module name
     */
    private function renameFile(string $filePath): string
    {
        $directory = dirname($filePath);
        $filename = basename($filePath, '.php');
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        
        // Replace "Example" with module name in filename
        $newFilename = str_replace('Example', $this->moduleName, $filename);
        
        return $directory . '/' . $newFilename . '.' . $extension;
    }

    /**
     * Replace placeholders in file content
     */
    private function replacePlaceholders(string $content): string
    {
        $replacements = [
            'Example' => $this->moduleName,
            'example' => $this->moduleLower,
            'EXAMPLE' => $this->moduleUpper,
        ];
        
        $content = str_replace(array_keys($replacements), array_values($replacements), $content);
        
        // Replace table name with custom table name (if different from module name)
        if ($this->tableName !== $this->moduleLower) {
            $content = str_replace("'{$this->moduleLower}'", "'{$this->tableName}'", $content);
            $content = str_replace("\"{$this->moduleLower}\"", "\"{$this->tableName}\"", $content);
            $content = str_replace("{$this->moduleLower}_", "{$this->tableName}_", $content);
        }
        
        return $content;
    }

    /**
     * Output generation summary
     */
    private function outputSummary(array $directories, array $files): void
    {
        echo "\n📊 Generation Summary:\n";
        echo "📁 Directories created: " . count($directories) . "\n";
        echo "📄 Files created: " . count($files) . "\n\n";
        
        if (!empty($directories)) {
            echo "📁 Directories:\n";
            foreach ($directories as $dir) {
                echo "  - {$dir}\n";
            }
            echo "\n";
        }
        
        if (!empty($files)) {
            echo "📄 Files:\n";
            foreach ($files as $file) {
                $relativePath = str_replace($this->projectRoot . '/', '', $file);
                echo "  - {$relativePath}\n";
            }
        }
    }
}

/**
 * Main execution function
 */
function main(): void
{
    $scriptPath = dirname(__FILE__);
    $projectRoot = dirname($scriptPath, 2);
    
    $args = array_slice($GLOBALS['argv'], 1);
    
    if (empty($args)) {
        echo "📖️ Usage: php scripts/generate-module.php --module=<ModuleName> [--table=<TableName>]\n";
        echo "\n📝 Examples:\n";
        echo "  php scripts/generate-module.php --module=Product\n";
        echo "  php scripts/generate-module.php --module=Product --table=products\n";
        echo "  php scripts/generate-module.php --module=Order --table=logistic_service\n";
        echo "  php scripts/generate-module.php --module=User --table=users\n";
        echo "  php scripts/generate-module.php --module=Blog --table=blog_posts\n";
        echo "  php scripts/generate-module.php --module=Payment --table=payment_transactions\n";
        exit(1);
    }
    
    // Parse arguments for --module and --table options
    $moduleName = null;
    $tableName = null;
    
    foreach ($args as $arg) {
        if (str_starts_with($arg, '--module=')) {
            $moduleName = substr($arg, 9); // Remove '--module=' prefix
        } elseif (str_starts_with($arg, '--module')) {
            // Handle space-separated format: --module Product
            $argParts = explode('=', $arg, 2);
            if (count($argParts) === 2) {
                $moduleName = $argParts[1];
            } else {
                // Find next argument as module name
                $argIndex = array_search($arg, $args);
                if ($argIndex !== false && isset($args[$argIndex + 1])) {
                    $moduleName = $args[$argIndex + 1];
                }
            }
        } elseif (str_starts_with($arg, '--table=')) {
            $tableName = substr($arg, 8); // Remove '--table=' prefix
        } elseif (str_starts_with($arg, '--table')) {
            // Handle space-separated format: --table table_name
            $argParts = explode('=', $arg, 2);
            if (count($argParts) === 2) {
                $tableName = $argParts[1];
            } else {
                // Find next argument as table name
                $argIndex = array_search($arg, $args);
                if ($argIndex !== false && isset($args[$argIndex + 1])) {
                    $tableName = $args[$argIndex + 1];
                }
            }
        }
    }
    
    if ($moduleName === null) {
        echo "❌ Error: --module option is required\n";
        echo "📖️ Usage: php scripts/generate-module.php --module=<ModuleName> [--table=<TableName>]\n";
        exit(1);
    }
    
    if (empty($moduleName)) {
        echo "❌ Error: Module name cannot be empty\n";
        exit(1);
    }
    
    try {
        $generator = new ModuleGenerator($projectRoot, $moduleName, $tableName);
        $generator->generate();
    } catch (Exception $e) {
        echo "❌ Error generating module: {$e->getMessage()}\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        exit(1);
    }
}

// Run the main function if this script is executed directly
if (php_sapi_name() === 'cli') {
    main();
}
