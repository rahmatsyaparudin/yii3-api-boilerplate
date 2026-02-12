<?php

declare(strict_types=1);

namespace App\Seeder;

// Domain Layer
use App\Domain\Example\Entity\Example;
// use App\Infrastructure\Persistence\Example\ExampleRepository;
use App\Domain\Example\Repository\ExampleRepositoryInterface;

// PSR Interfaces
use Psr\Clock\ClockInterface;

// Vendor Layer
use Yiisoft\Db\Connection\ConnectionInterface;

// Application Layer
use App\Application\Shared\Factory\DetailInfoFactory;
use App\Shared\Query\QueryConditionApplier;

// Infrastructure Layer
use App\Infrastructure\Seeder\AbstractSeederData;
use App\Infrastructure\Database\MongoDB\MongoDBService;
use Yiisoft\Aliases\Aliases;

/**
 * Seeds example table using Alice fixtures.
 */
final class SeedExampleData extends AbstractSeederData
{
    private ExampleRepositoryInterface $repository;

    // Fixture constants
    protected const YAML_FILE = 'example.yaml';
    protected const ENTITY_CLASS = Example::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        ExampleRepositoryInterface $repository
    ) {
        parent::__construct($db, $clock, $detailInfoFactory, $aliases);
        $this->repository = $repository;
    }

    protected function insertEntity(object $entity, mixed $detailInfo): void
    {
        // Create new entity with proper DetailInfo
        $newEntity = Example::create(
            $entity->getName(),
            $entity->getStatus(),
            $detailInfo
        );
        
        // Use repository to insert
        $this->repository->insert($newEntity);   
    }
}
