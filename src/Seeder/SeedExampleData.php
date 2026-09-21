<?php

declare(strict_types=1);

namespace App\Seeder;

// Domain Layer
use App\Application\Shared\Core\Factory\DetailInfoFactory;
// use App\Infrastructure\Common\Persistence\Example\ExampleRepository;
use App\Domain\Example\Entity\Example;
// PSR Interfaces
use App\Domain\Example\Repository\ExampleRepositoryInterface;
// Vendor Layer
use App\Infrastructure\Core\Seeder\AbstractSeederData;
// Application Layer
use Psr\Clock\ClockInterface;
// Infrastructure Layer
use Yiisoft\Aliases\Aliases;
use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * Seeds example table using Alice fixtures.
 */
final class SeedExampleData extends AbstractSeederData
{
    private ExampleRepositoryInterface $repository;

    // Fixture constants
    protected const YAML_FILE    = 'example.yaml';
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
