<?php

declare(strict_types=1);

namespace App\Seeder;

// Domain Layer
use App\Domain\Product\Entity\Product;
// use App\Infrastructure\Persistence\Product\ProductRepository;
use App\Domain\Product\Repository\ProductRepositoryInterface;

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
 * Seeds product table using Alice fixtures.
 */
final class SeedProductData extends AbstractSeederData
{
    private ProductRepositoryInterface $productRepository;

    // Fixture constants
    protected const YAML_FILE = 'product.yaml';
    protected const ENTITY_CLASS = Product::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        ProductRepositoryInterface $productRepository
    ) {
        parent::__construct($db, $clock, $detailInfoFactory, $aliases);
        $this->productRepository = $productRepository;
    }

    protected function insertEntity(object $entity, mixed $detailInfo): void
    {
        // Create new entity with proper DetailInfo
        $newEntity = Product::create(
            $entity->getName(),
            $entity->getStatus(),
            $detailInfo
        );
        
        // Use repository to insert
        $this->productRepository->insert($newEntity);   
    }
}
