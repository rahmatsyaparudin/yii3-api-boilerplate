<?php

declare(strict_types=1);

namespace App\Infrastructure\Concerns;

use App\Domain\Shared\ValueObject\SyncMdb;
use App\Infrastructure\Database\MongoDB\MongoDBService;

trait HasMongoDBSync
{
    private ?object $collection = null;

    /**
     * Inisialisasi koneksi MongoDB collection.
     * Dipanggil di constructor Repository.
     */
    private function initMongoDBSync(MongoDBService $service, string $collection): void
    {
        $this->collection = $service->getCollection($collection);
    }

    /**
     * Sinkronisasi data ke MongoDB.
     * @param object $entity Objek Entity (misal: Example)
     * @param string $schemaClass Nama class Schema lengkap (string)
     */
    private function syncMongoDB(object $entity, string $schemaClass): void
    {
        if ($this->collection === null) {
            $this->markAsNotSynced($entity);
            return;
        }

        try {
            $this->collection->updateOne(
                ['id' => $entity->getId()],
                ['$set' => $schemaClass::toArray($entity)],
                ['upsert' => true]
            );
            
            // Sync berhasil, reset sync status
            $syncMdb = $entity->getSyncMdb();
            if ($syncMdb === null || !$syncMdb->isSynced()) {
                $entity->setSyncMdb(SyncMdb::synced());
                $this->updateSyncStatusInDb(
                    id: $entity->getId(), 
                    status: SyncMdb::synced()->value()
                );
            }
            
        } catch (\MongoDB\Driver\Exception\Exception | \Exception $e) {
            $this->markAsNotSynced($entity);
        }
    }

    private function markAsNotSynced(object $entity): void
    {
        // Hanya update jika belum pending untuk efisiensi
        $syncMdb = $entity->getSyncMdb();
        if ($syncMdb === null || !$syncMdb->isPending()) {
            $entity->setSyncMdb(SyncMdb::pending());
            $this->updateSyncStatusInDb(
                id: $entity->getId(), 
                status: SyncMdb::pending()->value()
            );
        }
    }

    private function updateSyncStatusInDb(int $id, ?int $status): void
    {
        // Pastikan property $this->db tersedia di Repository yang menggunakan trait ini
        if (isset($this->db)) {
            $this->db->createCommand()
                ->update(self::TABLE_NAME, [SyncMdb::field() => $status], ['id' => $id])
                ->execute();
        }
    }
}