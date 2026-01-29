<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\MongoDB;

// Infrastructure Layer
use App\Infrastructure\Database\MongoDB\MongoDBService;

// Vendor Layer
use MongoDB\Collection;

abstract class AbstractMongoDBRepository
{
    protected Collection $collection;

    public function __construct(protected MongoDBService $mongoDBService)
    {
        $this->collection = $this->mongoDBService->getCollection($this->getCollectionName());
    }

    abstract protected function getCollectionName(): string;

    public function findOne(array $filter): ?array
    {
        $doc = $this->collection->findOne($filter);
        return $doc ? $this->mongoDBService->documentToArray($doc) : null;
    }

    public function insert(array $data): string
    {
        $result = $this->collection->insertOne($data);
        return (string) $result->getInsertedId();
    }
}