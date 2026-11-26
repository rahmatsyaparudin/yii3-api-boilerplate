<?php

declare(strict_types=1);

namespace App\Application\Shared\Core\Factory;

// Domain Layer
use App\Domain\Shared\Core\Contract\DateTimeProviderInterface;
use App\Domain\Shared\Core\Enum\SyncQueueStatus;
use App\Domain\Shared\Core\ValueObject\SyncFlag;
use App\Domain\Shared\Core\ValueObject\SyncIdentity;
// Infrastructure Layer
use App\Infrastructure\Core\Security\CurrentUser;

/**
 * Builder baris outbox untuk tabel `sync_queue`.
 *
 * Polanya sama dengan `SyncFlagFactory::buildPayload()` — factory ini hanya
 * menyusun array baris; repository module yang melakukan INSERT di dalam
 * transaksi yang sama dengan record bisnisnya (outbox pattern).
 *
 * Satu record → satu baris per tujuan. `destination_store_id = null`
 * berarti broadcast ke semua origin.
 */
final class SyncOutboxFactory
{
    public const TABLE = 'sync_queue';

    public function __construct(
        private DateTimeProviderInterface $dateTime,
        private CurrentUser $currentUser,
    ) {}

    /**
     * Susun satu baris antrian untuk satu tujuan.
     */
    public function buildRow(
        string $table,
        SyncIdentity $identity,
        SyncFlag $syncFlag,
        string $operation,
        ?int $destinationStoreId,
        array $payload = [],
    ): array {
        $actor = $this->currentUser->getActor();

        return [
            'table_name'           => $table,
            'record_uuid'          => $identity->getUuid(),
            'destination_store_id' => $destinationStoreId,
            'operation'            => $operation,
            'direction'            => $syncFlag->getDirection()->value,
            'payload'              => $payload === [] ? null : \json_encode($payload),
            'status'               => SyncQueueStatus::PENDING->value,
            'attempts'             => 0,
            'last_error'           => null,
            'synced_at'            => null,
            'created_at'           => $this->dateTime->database(),
            'created_by'           => $actor->getUsername(),
        ];
    }

    /**
     * Susun baris antrian untuk banyak tujuan sekaligus (broadcast bila
     * `$destinationStoreIds` kosong dan `broadcast = true`).
     *
     * @param int[] $destinationStoreIds
     */
    public function buildRows(
        string $table,
        SyncIdentity $identity,
        SyncFlag $syncFlag,
        string $operation,
        array $destinationStoreIds = [],
        array $payload = [],
        bool $broadcast = false,
    ): array {
        if ($destinationStoreIds === []) {
            if (!$broadcast && $syncFlag->getOriginId() === null) {
                return [];
            }

            return [
                $this->buildRow(
                    table: $table,
                    identity: $identity,
                    syncFlag: $syncFlag,
                    operation: $operation,
                    destinationStoreId: $broadcast ? null : $syncFlag->getOriginId(),
                    payload: $payload,
                ),
            ];
        }

        $rows = [];
        foreach ($destinationStoreIds as $destinationStoreId) {
            $rows[] = $this->buildRow(
                table: $table,
                identity: $identity,
                syncFlag: $syncFlag,
                operation: $operation,
                destinationStoreId: $destinationStoreId,
                payload: $payload,
            );
        }

        return $rows;
    }

    /**
     * Resolve daftar tujuan dari SyncFlag: origin_id eksplisit → satu
     * tujuan; null → broadcast (null) atau NONE bila direction tidak
     * mengarah ke origin.
     *
     * @return array<int, ?int>
     */
    public function resolveDestinations(SyncFlag $syncFlag): array
    {
        if (!$syncFlag->needsSyncToOrigin() && !$syncFlag->needsSyncToMaster()) {
            return [];
        }

        if ($syncFlag->getOriginId() !== null) {
            return [$syncFlag->getOriginId()];
        }

        // Tidak ada tujuan eksplisit → broadcast.
        return [null];
    }
}
