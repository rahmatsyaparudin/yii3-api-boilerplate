<?php

declare(strict_types=1);

namespace App\Infrastructure\Seeder;

// Application Layer
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\Shared\ValueObject\DetailInfo;
use App\Shared\ValueObject\Message;

// Vendor Layer
use Faker\Factory;
use Nelmio\Alice\Loader\NativeLoader;
use Yiisoft\Db\Connection\ConnectionInterface;
use Psr\Clock\ClockInterface;
use Yiisoft\Aliases\Aliases;
use App\Shared\Exception\BadRequestException;

/**
 * Abstract base class for seeders using Alice fixtures.
 */
abstract class AbstractSeederData
{
    protected ConnectionInterface $db;
    protected ClockInterface $clock;
    protected DetailInfoFactory $detailInfoFactory;
    protected Aliases $aliases;
    protected array $detailInfoObjects = [];

    /**
     * Constants to be defined by concrete seeders:
     * - protected const YAML_FILE
     * - protected const ENTITY_CLASS
     */

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
    ) {
        $this->db = $db;
        $this->clock = $clock;
        $this->detailInfoFactory = $detailInfoFactory;
        $this->aliases = $aliases;
        
        // Validate required constants
        $yamlFile = static::class . '::YAML_FILE';
        if (!defined($yamlFile) || empty(constant($yamlFile))) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'seeder.missing_yaml_file',
                    params: ['class' => static::class]
                )
            );
        }
        
        $entityClass = static::class . '::ENTITY_CLASS';
        if (!defined($entityClass) || empty(constant($entityClass))) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'seeder.missing_entity_class',
                    params: ['class' => static::class]
                )
            );
        }
    }

    public function run(int $count = 10): void
    {
        if (!$this->isDevelopmentEnvironment()) {
            return;
        }

        $objects = $this->loadFixtures($count);
        $this->seedDatabase($objects);
    }

    private function isDevelopmentEnvironment(): bool
    {
        $appEnv = $_ENV['APP_ENV'] ?? '';
        if (!in_array($appEnv, ['dev', 'development'], true)) {
            echo "Seed skipped: Can only be run in development environment.\n";
            echo "Current environment: {$appEnv}\n";
            return false;
        }
        return true;
    }

    private function loadFixtures(int $count = 10): array
    {
        $fixtureFile = $this->getFixtureFile();
        
        if (!file_exists($fixtureFile)) {
            echo "Fixture file not found: {$fixtureFile}\n";
            return [];
        }

        $faker = Factory::create();

        $fakerDir = $this->aliases->get('@src/Seeder/Faker');
        if (is_dir($fakerDir)) {
            $files = glob($fakerDir . '/*.php');
            if (is_array($files)) {
                foreach ($files as $file) {
                    $className = 'App\\Seeder\\Faker\\' . basename($file, '.php');
                    
                    if (class_exists($className) && is_subclass_of($className, \Faker\Provider\Base::class)) {
                        $reflection = new \ReflectionClass($className);
                        $provider = $reflection->newInstance($faker);
                        $faker->addProvider($provider);
                    }
                }
            }
        }

        $loader = new NativeLoader($faker);
        $objectSet = $loader->loadFile($fixtureFile);
        
        $allObjects = $objectSet->getObjects();
        
        $validEntities = array_filter(
            $allObjects,
            fn($object) => $this->isValidEntity($object)
        );
        
        $detailInfoObjects = array_filter(
            $allObjects,
            fn($object) => $object instanceof DetailInfo
        );
        
        $this->detailInfoObjects = $detailInfoObjects;
        
        return array_slice($validEntities, 0, $count);
    }

    private function seedDatabase(array $objects): void
    {
        if (empty($objects)) {
            echo "No entities found in fixtures.\n";
            return;
        }

        $count = 0;
        foreach ($objects as $entity) {
            $detailInfo = $this->getEntityDetailInfo($entity);
            
            $this->insertEntity($entity, $detailInfo);
            $count++;
        }

        echo "✅ Seeded {$count} {$this->getEntityType()} records\n";
    }

    private function getEntityDetailInfo(object $entity): mixed
    {
        // Get DetailInfo from the entity itself
        $entityDetailInfo = $entity->getDetailInfo();
        $detailInfoData = $entityDetailInfo->toArray();
        
        // If DetailInfo is empty, create new with change log
        if (empty($detailInfoData)) {
            return $this->detailInfoFactory
                ->create([])
                ->build();
        }
        
        // If DetailInfo has data, use that data
        return $this->detailInfoFactory
            ->create($detailInfoData)
            ->build();
    }

    protected function getFixtureFile(): string
    {
        $yamlFile = static::class . '::YAML_FILE';
        $fixtureFile = constant($yamlFile);
        return $this->aliases->get('@src/Seeder/Fixtures/' . $fixtureFile);
    }

    protected function getEntityType(): string
    {
        $entityClassConstant = static::class . '::ENTITY_CLASS';
        $entityClass = constant($entityClassConstant);
        
        // Try to get RESOURCE constant first
        if (defined($entityClass . '::RESOURCE')) {
            return constant($entityClass . '::RESOURCE');
        }
        
        // Try to call static getResource() method
        if (method_exists($entityClass, 'getResource')) {
            return $entityClass::getResource();
        }
        
        // Fallback to class name extraction
        $className = substr($entityClass, strrpos($entityClass, '\\') + 1);
        return $className;
    }

    final protected function isValidEntity(object $object): bool
    {
        $entityClassConstant = static::class . '::ENTITY_CLASS';
        $targetClass = constant($entityClassConstant);
        
        if (empty($targetClass)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'seeder.missing_entity_class',
                    params: ['class' => static::class]
                )
            );
        }

        return $object instanceof $targetClass;
    }
    
    abstract protected function insertEntity(object $entity, mixed $detailInfo): void;
}
