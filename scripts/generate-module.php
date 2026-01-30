#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Module Generator Script
 * 
 * This script generates new API modules based on the Example template structure.
 * It creates all necessary directories and files following the established patterns
 * for Yii3 API architecture with proper namespacing and file organization.
 * 
 * Usage:
 * php scripts/generate-module.php <ModuleName> <ModuleDescription>
 * 
 * Examples:
 * php scripts/generate.php Product "Product management module"
 * php scripts/generate.php Order "Order processing module"
 * php scripts/generate.php User "User management module"
 */

/**
 * Module Generator Class
 * 
 * Handles the generation of new API modules based on template structure.
 * Creates directories, copies template files, and performs string replacements
 * to customize the module according to the provided name.
 */
final class ModuleGenerator
{
    private string $projectRoot;
    private string $moduleName;
    private string $moduleDescription;
    private array $templateMap;

    /**
     * Module Generator constructor
     * 
     * @param string $projectRoot Root directory of the project
     * @param string $moduleName Name of the module to generate
     * @param string $moduleDescription Description of the module
     */
    public function __construct(string $projectRoot, string $moduleName, string $moduleDescription)
    {
        $this->projectRoot = $projectRoot;
        $this->moduleName = ucfirst($moduleName);
        $this->moduleDescription = $moduleDescription;
        
        $this->templateMap = [
            // Config files
            'config/common/access.php' => 'config/common/access.php',
            'config/common/aliases.php' => 'config/common/aliases.php',
            'config/common/params.php' => 'config/common/params.php',
            'config/common/routes.php' => 'config/common/routes.php',
            'config/common/di/infrastructure.php' => 'config/common/di/infrastructure.php',
            'config/common/di/repository.php' => 'config/common/di/repository.php',
            'config/common/di/seed.php' => 'config/common/di/seed.php',
            'config/common/di/service.php' => 'config/common/di/service.php',
            'config/common/di/translator.php' => 'config/common/di/translator.php',
            'config/console/commands.php' => 'config/console/commands.php',
            'config/console/params.php' => 'config/console/params.php',
            
            // API V1
            'src/Api/V1/Example' => "src/Api/V1/{$this->moduleName}",
            'src/Api/V1/Example/Controller' => "src/Api/V1/{$this->moduleName}/Controller",
            'src/Api/V1/Example/Request' => "src/Api/V1/{$this->moduleName}/Request",
            'src/Api/V1/Example/Response' => "src/Api/V1/{$this->moduleName}/Response",
            
            // Application
            'src/Application/Example' => "src/Application/{$this->moduleName}",
            'src/Application/Example/Service' => "src/Application/{$this->moduleName}/Service",
            'src/Application/Example/Factory' => "src/Application/{$this->moduleName}/Factory",
            
            // Console
            'src/Console/SeedExampleCommand.php' => "src/Console/Seed{$this->moduleName}Command.php",
            
            // Domain
            'src/Domain/Example' => "src/Domain/{$this->moduleName}",
            'src/Domain/Example/Entity' => "src/Domain/{$this->moduleName}/Entity",
            'src/Domain/Example/Repository' => "src/Domain/{$this->moduleName}/Repository",
            'src/Domain/Example/Service' => "src/Domain/{$this->moduleName}/Service",
            'src/Domain/Example/ValueObject' => "src/Domain/{$this->moduleName}/ValueObject",
            
            // Infrastructure
            'src/Infrastructure/Persistence/Example' => "src/Infrastructure/Persistence/{$this->moduleName}",
            'src/Infrastructure/Persistence/Example/ActiveRecord' => "src/Infrastructure/Persistence/{$this->moduleName}/ActiveRecord",
            'src/Infrastructure/Persistence/Example/Migration' => "src/Infrastructure/Persistence/{$this->moduleName}/Migration",
            'src/Infrastructure/Persistence/Example/Schema' => "src/Infrastructure/Persistence/{$this->moduleName}/Schema",
            
            // Migration
            'src/Migration' => 'src/Migration',
        ];
    }

    /**
     * Generate the complete module structure
     * 
     * Creates all directories and files based on the Example template,
     * performs string replacements to customize the module, and outputs
     * a summary of what was created.
     */
    public function generate(): void
    {
        echo "🚀 Generating module: {$this->moduleName}\n";
        echo "📝 Description: {$this->moduleDescription}\n\n";
        
        $createdFiles = [];
        $createdDirectories = [];
        
        // Create all directories first
        foreach ($this->templateMap as $source => $target) {
            if (is_dir($source)) {
                $this->createDirectory($target, $createdDirectories);
            }
        }
        
        // Copy and customize files
        foreach ($this->templateMap as $source => $target) {
            if (is_file($source)) {
                $this->copyAndCustomizeFile($source, $target, $createdFiles);
            }
        }
        
        // Output summary
        $this->outputSummary($createdDirectories, $createdFiles);
        $this->updateConfigFiles();
        
        echo "\n✅ Module '{$this->moduleName}' generated successfully!\n";
        echo "📁 Check the generated files and customize as needed.\n";
    }

    /**
     * Create directory if it doesn't exist
     */
    private function createDirectory(string $path, array &$createdDirectories): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
            $createdDirectories[] = $path;
            echo "📁 Created directory: {$path}\n";
        }
    }

    /**
     * Copy file and perform string replacements
     */
    private function copyAndCustomizeFile(string $source, string $target, array &$createdFiles): void
    {
        $targetDir = dirname($target);
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
            $createdDirectories[] = $targetDir;
        }
        
        $content = file_get_contents($source);
        $content = $this->replacePlaceholders($content);
        
        file_put_contents($target, $content);
        $createdFiles[] = $target;
        echo "📄 Created file: {$target}\n";
    }

    /**
     * Replace placeholders with module-specific values
     */
    private function replacePlaceholders(string $content): string
    {
        $replacements = [
            'Example' => $this->moduleName,
            'example' => strtolower($this->moduleName),
            'EXAMPLE' => strtoupper($this->moduleName),
            'example_module' => strtolower($this->moduleName) . '_module',
            'ExampleModule' => ucfirst($this->moduleName) . 'Module',
            'exampleDescription' => $this->moduleDescription,
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $content);
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

    /**
     * Update configuration files to include new module
     */
    private function updateConfigFiles(): void
    {
        $configFiles = [
            'config/common/di/repository.php',
            'config/common/di/service.php',
            'config/console/commands.php',
            'config/common/routes.php'
        ];
        
        foreach ($configFiles as $configFile) {
            $this->updateConfigFile($configFile);
        }
    }

    /**
     * Update a single configuration file
     */
    private function updateConfigFile(string $configFile): void
    {
        if (!file_exists($configFile)) {
            return;
        }
        
        $content = file_get_contents($configFile);
        
        // Add module to repository DI if it exists
        if (strpos($configFile, 'repository.php') !== false) {
            $content = $this->addModuleToRepositoryDi($content);
        }
        
        // Add module to service DI if it exists
        if (strpos($configFile, 'service.php') !== false) {
            $content = $this->addModuleToServiceDi($content);
        }
        
        // Add module to console commands if it exists
        if (strpos($configFile, 'commands.php') !== false) {
            $content = $this->addModuleToCommands($content);
        }
        
        // Add module to routes if it exists
        if (strpos($configFile, 'routes.php') !== false) {
            $content = $this->addModuleToRoutes($content);
        }
        
        file_put_contents($configFile, $content);
        echo "⚙️ Updated: {$configFile}\n";
    }

    /**
     * Add module to repository DI configuration
     */
    private function addModuleToRepositoryDi(string $content): string
    {
        $pattern = '/(\/\*\/\s*\/\* Repository DI \*\/\*\/)/';
        
        if (preg_match($pattern, $content)) {
            $newSection = "
    // {$this->moduleName} Repository DI
    {$this->moduleName}\\Repository\\{$this->moduleName}Repository::class => [
        'class' => {$this->moduleName}\\Repository\\{$this->moduleName}Repository::class,
        '__construct' => [
            {$this->moduleName}\\Domain\\{$this->moduleName}\\{$this->moduleName}RepositoryInterface \$repository,
        ],
    ],";
            
            return preg_replace($pattern, $newSection, $content);
        }
        
        return $content;
    }

    /**
     * Add module to service DI configuration
     */
    private function addModuleToServiceDi(string $content): string
    {
        $pattern = '/(\/\*\/\s*\/\* Service DI \*\/\*\/)/';
        
        if (preg_match($pattern, $content)) {
            $newSection = "
    // {$this->moduleName} Service DI
    {$this->moduleName}\\Application\\{$this->moduleName}\\{$this->moduleName}Service::class => [
        'class' => {$this->moduleName}\\Application\\{$this->moduleName}\\{$this->moduleName}Service::class,
        '__construct' => [
            {$this->moduleName}\\Domain\\{$this->moduleName}\\{$this->moduleName}RepositoryInterface \$repository,
            {$this->moduleName}\\Domain\\{$this->moduleName}\\{$this->moduleName}ServiceInterface \$service,
        ],
    ],";
            
            return preg_replace($pattern, $newSection, $content);
        }
        
        return $content;
    }

    /**
     * Add module to console commands
     */
    private function addModuleToCommands(string $content): string
    {
        $pattern = '/return \[\s*\'app:console\'\s*=>\s*\[\s*\'App\\\\Console\\\\.*\'\s*\],/';
        
        if (preg_match($pattern, $content)) {
            $newCommand = "'App\\\\Console\\\\Seed{$this->moduleName}Command',";
            $content = str_replace("'app:console'", "'app:console',", $content);
            $content = str_replace("'App\\\\Console\\\\HelloCommand',", "{$newCommand},", $content);
            
            // Also add the import at the top if not present
            if (!str_contains($content, "use App\\Console\\Seed{$this->moduleName}Command;")) {
                $content = preg_replace(
                    '/^<\?php.*\n/',
                    "<?php\n\nuse App\\Console\\Seed{$this->moduleName}Command;\n",
                    $content
                );
            }
        }
        
        return $content;
    }

    /**
     * Add module to routes configuration
     */
    private function addModuleToRoutes(string $content): string
    {
        $pattern = '/return \[\s*\'app:routes\'\s*=>\s*\[\s*\'App\\\\Api\\\\V1\\\\.*\'\s*\],/';
        
        if (preg_match($pattern, $content)) {
            $newRoute = "'App\\\\Api\\\\V1\\\\{$this->moduleName}\\\\Controller\\\\{$this->moduleName}Controller',";
            $content = str_replace("'app:routes'", "'app:routes',", $content);
            $content = str_replace("'App\\\\Api\\\\V1\\\\Example\\\\ExampleController',", $newRoute, $content);
            
            // Also add the import at the top if not present
            if (!str_contains($content, "use App\\Api\\V1\\{$this->moduleName}\\Controller\\{$this->moduleName}Controller;")) {
                $content = preg_replace(
                    '/^<\?php.*\n/',
                    "<?php\n\nuse App\\Api\\V1\\{$this->moduleName}\\Controller\\{$this->moduleName}Controller;\n",
                    $content
                );
            }
        }
        
        return $content;
    }
}

/**
 * Main execution function
 * 
 * Parses command line arguments and runs the module generator.
 */
function main(): void
{
    $scriptPath = dirname(__FILE__);
    $projectRoot = dirname($scriptPath, 2);
    
    $args = array_slice($GLOBALS['argv'], 1);
    
    if (count($args) < 2) {
        echo "📖️ Usage: php scripts/generate-module.php <ModuleName> <ModuleDescription>\n";
        echo "\n📝 Examples:\n";
        echo "  php scripts/generate-module.php Product \"Product management module\"\n";
        echo "  php scripts/generate-module.php Order \"Order processing module\"\n";
        echo "  php scripts/generate-module.php User \"User management module\"\n";
        echo "  php scripts/generate-module.php Blog \"Blog content management\"\n";
        echo "  php scripts/generate-module.php Payment \"Payment processing module\"\n";
        exit(1);
    }
    
    $moduleName = $args[0];
    $moduleDescription = $args[1];
    
    try {
        $generator = new ModuleGenerator($projectRoot, $moduleName, $moduleDescription);
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
