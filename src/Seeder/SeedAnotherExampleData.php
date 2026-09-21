<?php

declare(strict_types=1);

namespace App\Seeder;

// Domain Layer
use App\Application\Shared\Core\Factory\DetailInfoFactory;
use App\Domain\AnotherExample\Entity\AnotherExample;
// PSR Interfaces
use App\Domain\AnotherExample\Repository\AnotherExampleRepositoryInterface;
// Vendor Layer
use App\Infrastructure\Core\Seeder\AbstractSeederData;
// Application Layer
use Psr\Clock\ClockInterface;
// Infrastructure Layer
use Yiisoft\Aliases\Aliases;
use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * Seeds another_example table using Alice fixtures.
 */
final class SeedAnotherExampleData extends AbstractSeederData
{
    private AnotherExampleRepositoryInterface $repository;

    // Fixture constants
    protected const YAML_FILE    = 'anotherexample.yaml';
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
            syncFlag: $entity->getSyncFlag(),
            exampleId: $entity->getExampleId(),
        );

        // Use repository to insert
        $this->repository->insert($newEntity);
    }
}
