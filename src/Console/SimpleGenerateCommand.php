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

final class SimpleGenerateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('simple-generate')
            ->setDescription('Generate boilerplate code for entities')
            ->addArgument('type', InputArgument::REQUIRED, 'Type of code to generate (entity, repository, service, api, crud)')
            ->addArgument('entities', InputArgument::IS_ARRAY, 'Entity names to generate (space-separated)', ['Example'])
            ->addOption('with-lock-version', 'l', InputOption::VALUE_NONE, 'Include optimistic locking features')
            ->addOption('version', 'v', InputOption::VALUE_OPTIONAL, 'API version', '1')
            ->setHelp('Generate boilerplate code for DDD architecture');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $type = $input->getArgument('type');
        $entities = $input->getArgument('entities');
        $withLockVersion = $input->getOption('with-lock-version');
        $apiVersion = $input->getOption('version');

        $io->title('Simple Code Generator');
        $io->text("Generating {$type} for: " . implode(', ', $entities));

        $filesystem = new Filesystem();
        
        foreach ($entities as $entity) {
            $this->generateCode($type, $entity, $withLockVersion, $apiVersion, $filesystem, $io);
        }

        $io->success('Code generation completed!');
        return Command::SUCCESS;
    }

    private function generateCode(string $type, string $entity, bool $withLockVersion, string $apiVersion, Filesystem $filesystem, SymfonyStyle $io): void
    {
        $entityName = ucfirst($entity);
        $entityLower = strtolower($entity);
        
        switch ($type) {
            case 'entity':
                $this->generateEntity($entityName, $withLockVersion, $filesystem, $io);
                break;
            case 'repository':
                $this->generateRepository($entityName, $filesystem, $io);
                break;
            case 'service':
                $this->generateService($entityName, $filesystem, $io);
                break;
            case 'api':
                $this->generateApi($entityName, $apiVersion, $filesystem, $io);
                break;
            case 'crud':
                $this->generateCrud($entityName, $withLockVersion, $apiVersion, $filesystem, $io);
                break;
            default:
                $io->error("Unknown type: {$type}");
        }
    }

    private function generateEntity(string $entityName, bool $withLockVersion, Filesystem $filesystem, SymfonyStyle $io): void
    {
        $template = $this->getEntityTemplate($entityName, $withLockVersion);
        $path = "src/Domain/{$entityName}/Entity/{$entityName}.php";
        
        $this->createFile($path, $template, $filesystem, $io);
    }

    private function generateRepository(string $entityName, Filesystem $filesystem, SymfonyStyle $io): void
    {
        // Generate Interface
        $interfaceTemplate = $this->getRepositoryInterfaceTemplate($entityName);
        $interfacePath = "src/Domain/{$entityName}/Repository/{$entityName}RepositoryInterface.php";
        $this->createFile($interfacePath, $interfaceTemplate, $filesystem, $io);

        // Generate Implementation
        $implTemplate = $this->getRepositoryTemplate($entityName);
        $implPath = "src/Infrastructure/Persistence/{$entityName}/{$entityName}Repository.php";
        $this->createFile($implPath, $implTemplate, $filesystem, $io);
    }

    private function generateService(string $entityName, Filesystem $filesystem, SymfonyStyle $io): void
    {
        $template = $this->getServiceTemplate($entityName);
        $path = "src/Application/{$entityName}/{$entityName}ApplicationService.php";
        $this->createFile($path, $template, $filesystem, $io);
    }

    private function generateApi(string $entityName, string $apiVersion, Filesystem $filesystem, SymfonyStyle $io): void
    {
        $actions = ['Create', 'Update', 'Delete', 'View', 'Data'];
        
        foreach ($actions as $action) {
            $template = $this->getActionTemplate($entityName, $action);
            $path = "src/Api/V{$apiVersion}/{$entityName}/Action/{$entityName}{$action}Action.php";
            $this->createFile($path, $template, $filesystem, $io);
        }

        // Generate Validator
        $validatorTemplate = $this->getValidatorTemplate($entityName);
        $validatorPath = "src/Api/V{$apiVersion}/{$entityName}/Validation/{$entityName}InputValidator.php";
        $this->createFile($validatorPath, $validatorTemplate, $filesystem, $io);
    }

    private function generateCrud(string $entityName, bool $withLockVersion, string $apiVersion, Filesystem $filesystem, SymfonyStyle $io): void
    {
        $this->generateEntity($entityName, $withLockVersion, $filesystem, $io);
        $this->generateRepository($entityName, $filesystem, $io);
        $this->generateService($entityName, $filesystem, $io);
        $this->generateApi($entityName, $apiVersion, $filesystem, $io);
        
        // Generate Commands and DTOs
        $this->generateCommandsAndDtos($entityName, $filesystem, $io);
    }

    private function generateCommandsAndDtos(string $entityName, Filesystem $filesystem, SymfonyStyle $io): void
    {
        // Create Command
        $createCommandTemplate = $this->getCreateCommandTemplate($entityName);
        $createCommandPath = "src/Application/{$entityName}/Command/Create{$entityName}Command.php";
        $this->createFile($createCommandPath, $createCommandTemplate, $filesystem, $io);

        // Update Command
        $updateCommandTemplate = $this->getUpdateCommandTemplate($entityName);
        $updateCommandPath = "src/Application/{$entityName}/Command/Update{$entityName}Command.php";
        $this->createFile($updateCommandPath, $updateCommandTemplate, $filesystem, $io);

        // Response DTO
        $responseTemplate = $this->getResponseTemplate($entityName);
        $responsePath = "src/Application/{$entityName}/Dto/{$entityName}Response.php";
        $this->createFile($responsePath, $responseTemplate, $filesystem, $io);
    }

    private function createFile(string $path, string $content, Filesystem $filesystem, SymfonyStyle $io): void
    {
        $dir = dirname($path);
        if (!$filesystem->exists($dir)) {
            $filesystem->mkdir($dir);
        }

        $filesystem->dumpFile($path, $content);
        $io->text("Created: {$path}");
    }

    private function getEntityTemplate(string $entityName, bool $withLockVersion): string
    {
        $lockVersionTrait = $withLockVersion ? "use App\\Domain\\Shared\\Concerns\\Entity\\OptimisticLock;" : "";
        $lockVersionProperty = $withLockVersion ? "?LockVersion \$lockVersion = null" : "";
        $lockVersionParam = $withLockVersion ? ", ?LockVersion \$lockVersion = null" : "";
        
        return "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Domain\\{$entityName}\\Entity;\n\nuse App\\Domain\\Shared\\ValueObject\\DetailInfo;\nuse App\\Domain\\Shared\\ValueObject\\Status;\n" . 
               ($withLockVersion ? "use App\\Domain\\Shared\\ValueObject\\LockVersion;\n" : "") .
               "use App\\Domain\\Shared\\Concerns\\Entity\\Stateful;\nuse App\\Domain\\Shared\\Concerns\\Entity\\Identifiable;\nuse App\\Domain\\Shared\\Concerns\\Entity\\Descriptive;\n" .
               $lockVersionTrait . "\n\nfinal class {$entityName}\n{\n    use Identifiable, Stateful, Descriptive" . 
               ($withLockVersion ? ", OptimisticLock" : "") . ";\n\n    public const RESOURCE = '{$entityName}';\n\n    protected function __construct(\n        private readonly ?int \$id,\n        private string \$name,\n        private Status \$status,\n        private DetailInfo \$detailInfo,\n        private ?int \$syncMdb = null,\n        {$lockVersionProperty}\n    ) {\n        \$this->resource = self::RESOURCE;\n    }\n\n    public static function create(\n        string \$name,\n        Status \$status,\n        DetailInfo \$detailInfo,\n        ?int \$syncMdb = null,\n        {$lockVersionParam}\n    ): self {\n        return new self(\n            id: null,\n            name: \$name,\n            status: \$status,\n            detailInfo: \$detailInfo,\n            syncMdb: \$syncMdb,\n            lockVersion: \$lockVersion\n        );\n    }\n\n    public static function reconstitute(\n        int \$id,\n        string \$name,\n        Status \$status,\n        DetailInfo \$detailInfo,\n        ?int \$syncMdb = null,\n        int \$lockVersion = " . ($withLockVersion ? "LockVersion::DEFAULT_VALUE" : "1") . "\n    ): self {\n        return new self(\n            id: \$id,\n            name: \$name,\n            status: \$status,\n            detailInfo: \$detailInfo,\n            syncMdb: \$syncMdb,\n            lockVersion: " . ($withLockVersion ? "LockVersion::fromInt(\$lockVersion)" : "null") . "\n        );\n    }\n\n    // Getters\n    public function getId(): ?int { return \$this->id; }\n    public function getName(): string { return \$this->name; }\n    public function getStatus(): Status { return \$this->status; }\n    public function getDetailInfo(): DetailInfo { return \$this->detailInfo; }\n    public function getSyncMdb(): ?int { return \$this->syncMdb; }\n" . 
               ($withLockVersion ? "    public function getLockVersion(): LockVersion { return \$this->lockVersion ?? LockVersion::create(); }\n" : "") .
               "\n    // Business methods can be added here\n}";
    }

    private function getRepositoryInterfaceTemplate(string $entityName): string
    {
        return "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Domain\\{$entityName}\\Repository;\n\nuse App\\Domain\\{$entityName}\\Entity\\{$entityName};\nuse App\\Shared\\Dto\\SearchCriteria;\nuse App\\Shared\\Dto\\PaginatedResult;\n\ninterface {$entityName}RepositoryInterface\n{\n    public function findById(int \$id): ?{$entityName};\n    public function findByName(string \$name): ?{$entityName};\n    public function existsByName(string \$name): bool;\n    public function save({$entityName} \${$entityName}): {$entityName};\n    public function delete({$entityName} \${$entityName}): {$entityName};\n    public function restore(int \$id): ?{$entityName};\n    public function list(SearchCriteria \$criteria): PaginatedResult;\n}";
    }

    private function getRepositoryTemplate(string $entityName): string
    {
        return "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Infrastructure\\Persistence\\{$entityName};\n\nuse App\\Domain\\{$entityName}\\Entity\\{$entityName};\nuse App\\Domain\\{$entityName}\\Repository\\{$entityName}RepositoryInterface;\nuse App\\Domain\\Shared\\ValueObject\\Status;\nuse App\\Domain\\Shared\\ValueObject\\DetailInfo;\nuse Yiisoft\\Db\\Connection\\ConnectionInterface;\nuse Yiisoft\\Db\\Query\\Query;\nuse App\\Shared\\Query\\QueryConditionApplier;\nuse App\\Shared\\Dto\\SearchCriteria;\nuse App\\Shared\\Dto\\PaginatedResult;\nuse App\\Infrastructure\\Concerns\\HasCoreFeatures;\n\nfinal class {$entityName}Repository implements {$entityName}RepositoryInterface\n{\n    use HasCoreFeatures;\n    \n    private const TABLE = '" . strtolower($entityName) . "';\n\n    public function __construct(\n        private QueryConditionApplier \$queryConditionApplier,\n        private ConnectionInterface \$db,\n    ) {}\n\n    public function findById(int \$id): ?{$entityName}\n    {\n        // Implementation here\n        return null;\n    }\n\n    public function save({$entityName} \${$entityName}): {$entityName}\n    {\n        // Implementation here\n        return \${$entityName};\n    }\n\n    // Other methods...\n}";
    }

    private function getServiceTemplate(string $entityName): string
    {
        return "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Application\\{$entityName};\n\nuse App\\Application\\{$entityName}\\Command\\Create{$entityName}Command;\nuse App\\Application\\{$entityName}\\Command\\Update{$entityName}Command;\nuse App\\Application\\{$entityName}\\Dto\\{$entityName}Response;\nuse App\\Domain\\{$entityName}\\Entity\\{$entityName};\nuse App\\Domain\\{$entityName}\\Repository\\{$entityName}RepositoryInterface;\nuse App\\Domain\\Shared\\ValueObject\\Status;\n\nfinal class {$entityName}ApplicationService\n{\n    public function __construct(\n        private {$entityName}RepositoryInterface \$repository\n    ) {}\n\n    public function create(Create{$entityName}Command \$command): {$entityName}Response\n    {\n        // Implementation here\n        return new {$entityName}Response(/* ... */);\n    }\n\n    public function update(int \$id, Update{$entityName}Command \$command): {$entityName}Response\n    {\n        // Implementation here\n        return new {$entityName}Response(/* ... */);\n    }\n\n    // Other methods...\n}";
    }

    private function getActionTemplate(string $entityName, string $action): string
    {
        return "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Api\\V1\\{$entityName}\\Action;\n\nuse App\\Application\\{$entityName}\\{$entityName}ApplicationService;\nuse App\\Api\\Shared\\ResponseFactory;\nuse Psr\\Http\\Message\\ResponseInterface;\nuse Psr\\Http\\Message\\ServerRequestInterface;\n\nfinal class {$entityName}{$action}Action\n{\n    public function __construct(\n        private {$entityName}ApplicationService \$service,\n        private ResponseFactory \$responseFactory\n    ) {}\n\n    public function __invoke(ServerRequestInterface \$request): ResponseInterface\n    {\n        // Implementation here\n        return \$this->responseFactory->success([]);\n    }\n}";
    }

    private function getValidatorTemplate(string $entityName): string
    {
        return "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Api\\V1\\{$entityName}\\Validation;\n\nuse App\\Shared\\Validation\\AbstractValidator;\nuse App\\Shared\\Validation\\ValidationContext;\nuse Yiisoft\\Validator\\Rule\\Required;\nuse Yiisoft\\Validator\\Rule\\StringValue;\nuse Yiisoft\\Validator\\Rule\\Length;\n\nfinal class {$entityName}InputValidator extends AbstractValidator\n{\n    protected function rules(string \$context): array\n    {\n        return match (\$context) {\n            ValidationContext::CREATE => [\n                'name' => [\n                    new Required(),\n                    new StringValue(),\n                    new Length(min: 1, max: 255),\n                ],\n            ],\n            ValidationContext::UPDATE => [\n                'name' => [\n                    new StringValue(),\n                    new Length(min: 1, max: 255),\n                ],\n            ],\n            default => [],\n        };\n    }\n}";
    }

    private function getCreateCommandTemplate(string $entityName): string
    {
        return "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Application\\{$entityName}\\Command;\n\nfinal readonly class Create{$entityName}Command\n{\n    public function __construct(\n        public string \$name,\n        public int \$status,\n        public ?array \$detailInfo,\n        public ?bool \$syncMdb = null,\n    ) {}\n}";
    }

    private function getUpdateCommandTemplate(string $entityName): string
    {
        return "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Application\\{$entityName}\\Command;\n\nfinal readonly class Update{$entityName}Command\n{\n    public function __construct(\n        public int \$id,\n        public ?string \$name,\n        public ?int \$status,\n        public ?array \$detailInfo,\n        public ?bool \$syncMdb,\n        public ?int \$lockVersion,\n    ) {}\n}";
    }

    private function getResponseTemplate(string $entityName): string
    {
        return "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Application\\{$entityName}\\Dto;\n\nuse App\\Domain\\{$entityName}\\Entity\\{$entityName};\n\nfinal readonly class {$entityName}Response\n{\n    public function __construct(\n        public int \$id,\n        public string \$name,\n        public int \$status,\n        public bool \$sync_mdb,\n        public array \$detail_info,\n        public int \$lock_version,\n    ) {}\n\n    public static function fromEntity({$entityName} \${$entityName}): self\n    {\n        return new self(\n            id: \${$entityName}->getId(),\n            name: \${$entityName}->getName(),\n            status: \${$entityName}->getStatus()->value(),\n            sync_mdb: \${$entityName}->getSyncMdb() !== null,\n            detail_info: \${$entityName}->getDetailInfo()->toArray(),\n            lock_version: \${$entityName}->getLockVersion()->value(),\n        );\n    }\n}";
    }
}
