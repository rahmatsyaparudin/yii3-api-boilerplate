<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

// Vendor Layer
use MongoDB\Client;
use MongoDB\Database;
use MongoDB\Collection;

final class MongoService
{
    private Database $database;

    public function __construct(
        private ?Client $client,
        private string $dbName,
        private bool $enabled = true
    ) {
        if ($this->enabled && $client !== null) {
            try {
                $this->database = $client->selectDatabase($dbName);
            } catch (\Exception $e) {
                $this->enabled = false;
            }
        }
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    private function getSafeNode(): object
    {
        if (!$this->enabled || $this->database === null) {
            return $this->createNullObject();
        }

        return $this->database;
    }

    private function createNullObject(): object
    {
        return new class {
            public function __call(string $name, array $args): mixed 
            {
                return $this; 
            }
        };
    }

    public function getCollection(string $collectionName): mixed
    {
        return $this->getSafeNode()->selectCollection($collectionName);
    }

    public function find(string $collection, array $filter = [], array $options = []): array
    {
        return $this->getSafeNode()->selectCollection($collection)->find($filter, $options)->toArray();
    }

    public function toArray(mixed $data): array
    {
        return json_decode(json_encode($data), true);
    }

    public function documentToArray(object|array $document): array
    {
        return json_decode(json_encode($document), true);
    }
}