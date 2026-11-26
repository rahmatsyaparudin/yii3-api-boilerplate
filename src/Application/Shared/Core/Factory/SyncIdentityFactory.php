<?php

declare(strict_types=1);

namespace App\Application\Shared\Core\Factory;

// Domain Layer
use App\Domain\Shared\Core\Enum\DocRole;
use App\Domain\Shared\Core\ValueObject\SyncIdentity;
// Shared Layer
use App\Shared\Core\Exception\BadRequestException;
use App\Shared\Core\ValueObject\Message;

/**
 * Factory untuk membangun `SyncIdentity` — penulis uuid + provenance record.
 *
 * `store_id` instance ini diambil dari params `app/sync`
 * (`$_ENV['app.sync.store_id']`; `0` = cloud/master, `>0` = id store/origin).
 */
final class SyncIdentityFactory
{
    public function __construct(
        private array $params = [],
    ) {}

    /**
     * Id store/instance tempat proses ini berjalan.
     */
    public function storeId(): ?int
    {
        $id = (int) ($this->params['store_id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    /**
     * Identitas untuk record baru yang ditulis di store ini —
     * uuid digenerate otomatis, doc_role = LOCAL.
     */
    public function create(
        ?string $uuid = null,
        ?int $sourceStoreId = null,
        ?string $docRole = null,
    ): SyncIdentity {
        return SyncIdentity::create(
            uuid: $uuid,
            sourceStoreId: $sourceStoreId ?? $this->storeId(),
            docRole: $docRole === null
                ? DocRole::LOCAL
                : DocRole::fromValue($docRole),
        );
    }

    /**
     * Shortcut untuk record lokal baru.
     */
    public function local(?int $sourceStoreId = null): SyncIdentity
    {
        return SyncIdentity::local(sourceStoreId: $sourceStoreId ?? $this->storeId());
    }

    /**
     * Identitas yang harus ditulis saat meng-apply record hasil sync —
     * uuid & source_store_id dipertahankan dari pengirim, doc_role jadi COPY.
     */
    public function incomingCopy(string $uuid, ?int $sourceStoreId): SyncIdentity
    {
        return SyncIdentity::copy(uuid: $uuid, sourceStoreId: $sourceStoreId);
    }

    /**
     * Identitas hasil apply yang masih boleh diteruskan ke tujuan lain.
     */
    public function incomingInterstore(string $uuid, ?int $sourceStoreId): SyncIdentity
    {
        return SyncIdentity::interstore(uuid: $uuid, sourceStoreId: $sourceStoreId);
    }

    /**
     * Hydrate dari request body / array input.
     */
    public function fromArray(array $data): SyncIdentity
    {
        return SyncIdentity::fromArray($data);
    }

    /**
     * Hydrate dari database row.
     */
    public function fromRecord(array $row): SyncIdentity
    {
        return SyncIdentity::fromArray($row);
    }

    /**
     * Hydrate dari entity (getter getUuid/getSourceStoreId/getDocRoleValue/getDocRole).
     */
    public function fromEntity(object $entity): SyncIdentity
    {
        return SyncIdentity::fromEntity($entity);
    }

    /**
     * Anti echo-loop: record yang source_store_id-nya sama dengan store
     * ini tidak boleh di-apply lagi oleh applier.
     */
    public function isOwnRecord(SyncIdentity $identity): bool
    {
        return $identity->isOwnedBy($this->storeId());
    }

    /**
     * Guard aturan edit: hanya record LOCAL yang boleh diubah user.
     *
     * @throws BadRequestException jika doc_role bukan LOCAL.
     */
    public function assertEditableByUser(SyncIdentity $identity): void
    {
        if ($identity->isEditableByUser()) {
            return;
        }

        throw new BadRequestException(
            translate: Message::create(
                domain: 'validation',
                key: 'doc_role.not_editable',
                params: [
                    'doc_role' => $identity->getDocRole()->value,
                ]
            )
        );
    }
}
