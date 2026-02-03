<?php

declare(strict_types=1);

namespace App\Infrastructure\Concerns;

use App\Infrastructure\Database\MongoDB\MongoDBService;

trait HasMongoDBSync
{
    private ?object $collection = null;

    /**
     * Inisialisasi koneksi MongoDB collection.
     * Dipanggil di constructor Repository.
     */
    private function initMongoDBSync(MongoDBService $mongoDBService, string $tableName): void
    {
        $this->collection = $mongoDBService->getCollection($tableName);
    }

    /**
     * Sinkronisasi data ke MongoDB.
     * @param object $entity Objek Entity (misal: Example)
     * @param string $schemaClass Nama class Schema lengkap (string)
     */
    private function syncMongoDB(object $entity, string $schemaClass): void
    {
        if ($this->collection !== null) {
            $this->collection->updateOne(
                ['id' => $entity->getId()],
                ['$set' => $schemaClass::toArray($entity)],
                ['upsert' => true]
            );
        }
    }
}