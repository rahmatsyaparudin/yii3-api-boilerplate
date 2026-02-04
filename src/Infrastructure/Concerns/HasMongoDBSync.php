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
            if ($entity->getSyncMdb() !== null) {
                $entity->setSyncMdb(null);
                $this->updateSyncStatusInDb($entity->getId(), null);
            }
            
        } catch (\MongoDB\Driver\Exception\ConnectionTimeoutException $e) {
            // Handle connection timeout (code 13053)
            $this->markAsNotSynced($entity);
            
        } catch (\MongoDB\Driver\Exception\ServerSelectionTimeoutException $e) {
            // Handle server selection timeout
            $this->markAsNotSynced($entity);
            
        } catch (\MongoDB\Driver\Exception\RuntimeException $e) {
            // Handle other MongoDB runtime errors
            $this->markAsNotSynced($entity);
            
        } catch (\MongoDB\Driver\Exception\Exception $e) {
            // Handle general MongoDB exceptions
            $this->markAsNotSynced($entity);
            
        } catch (\Exception $e) {
            // Handle any other unexpected errors
            $this->markAsNotSynced($entity);
        }
    }

    private function markAsNotSynced(object $entity): void
    {
        // Hanya update jika belum bernilai 1 untuk efisiensi
        if ($entity->getSyncMdb() !== 1) {
            $entity->setSyncMdb(1);
            $this->updateSyncStatusInDb($entity->getId(), 1);
        }
    }

    private function updateSyncStatusInDb(int $id, ?int $status): void
    {
        // Pastikan property $this->db tersedia di Repository yang menggunakan trait ini
        if (isset($this->db)) {
            $this->db->createCommand()
                ->update(self::TABLE_NAME, ['sync_mdb' => $status], ['id' => $id])
                ->execute();
        }
    }
}