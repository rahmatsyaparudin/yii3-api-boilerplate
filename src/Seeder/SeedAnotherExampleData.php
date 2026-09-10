<?php

declare(strict_types=1);

namespace App\Seeder;

// Domain Layer
use App\Domain\AnotherExample\Entity\AnotherExample;
use App\Domain\AnotherExample\Repository\AnotherExampleRepositoryInterface;

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
 * Seeds another_example table using Alice fixtures.
 */
final class SeedAnotherExampleData extends AbstractSeederData
{
    private AnotherExampleRepositoryInterface $repository;

    // Fixture constants
    protected const YAML_FILE = 'anotherexample.yaml';
    protected const ENTITY_CLASS = AnotherExample::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        AnotherExampleRepositoryInterface $repository
    ) {
        parent::__construct($db, $clock, $detailInfoFactory, $aliases);
        $this->repository = $repository;
    }

    protected function insertEntity(object $entity, mixed $detailInfo): void
    {
        // Create new entity with proper DetailInfo
        $newEntity = AnotherExample::create(
            name: $entity->getName(),
            status: $entity->getStatus(),
            detailInfo: $detailInfo,
            syncMdb: $entity->getSyncMdb(),
            exampleId: $entity->getExampleId(),
        );
        
        // Use repository to insert
        $this->repository->insert($newEntity);   
    }
}
