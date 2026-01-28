<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

// Infrastructure Layer
use App\Infrastructure\Database\MongoService;

// Vendor Layer
use MongoDB\Collection;

abstract class AbstractMongoRepository
{
    protected Collection $collection;

    public function __construct(protected MongoService $mongoService)
    {
        $this->collection = $this->mongoService->getCollection($this->getCollectionName());
    }

    abstract protected function getCollectionName(): string;

    public function findOne(array $filter): ?array
    {
        $doc = $this->collection->findOne($filter);
        return $doc ? $this->mongoService->documentToArray($doc) : null;
    }

    public function insert(array $data): string
    {
        $result = $this->collection->insertOne($data);
        return (string) $result->getInsertedId();
    }
}