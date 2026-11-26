<?php

declare(strict_types=1);

namespace App\Domain\Shared\Core\ValueObject;

use App\Domain\Shared\Core\Enum\DocRole;
use App\Shared\Core\Enums\AppConstants;

/**
 * Value Object untuk identitas global record yang ikut sync antar store.
 *
 * Setiap record sync-aware memiliki:
 * - uuid            : identitas global yang stabil lintas store (id lokal beda-beda).
 * - source_store_id : id store yang pertama kali menulis record (provenance,
 *                     dipakai applier untuk skip diri sendiri — anti echo-loop).
 * - doc_role        : peran record — LOCAL/INTERSTORE/COPY (aturan editability).
 */
final readonly class SyncIdentity
{
    private function __construct(
        private string $uuid,
        private ?int $sourceStoreId,
        private DocRole $docRole,
    ) {
    }

    public static function fieldUuid(): string
    {
        return AppConstants::UUID;
    }

    public static function fieldSourceStoreId(): string
    {
        return AppConstants::SOURCE_STORE_ID;
    }

    public static function fieldDocRole(): string
    {
        return AppConstants::DOC_ROLE;
    }

    public static function create(
        ?string $uuid = null,
        ?int $sourceStoreId = null,
        DocRole $docRole = DocRole::LOCAL,
    ): self {
        return new self(
            uuid: $uuid === null || $uuid === '' ? self::newUuid() : $uuid,
            sourceStoreId: $sourceStoreId,
            docRole: $docRole,
        );
    }

    public static function fromArray(array $data): self
    {
        return self::create(
            uuid: isset($data[self::fieldUuid()])
                ? (string) $data[self::fieldUuid()]
                : null,
            sourceStoreId: isset($data[self::fieldSourceStoreId()])
                ? (int) $data[self::fieldSourceStoreId()]
                : null,
            docRole: isset($data[self::fieldDocRole()])
                ? DocRole::fromValue((string) $data[self::fieldDocRole()])
                : DocRole::LOCAL,
        );
    }

    public static function fromEntity(object $entity): self
    {
        $uuid = \method_exists($entity, 'getUuid')
            ? $entity->getUuid()
            : null;

        $sourceStoreId = \method_exists($entity, 'getSourceStoreId')
            ? $entity->getSourceStoreId()
            : null;

        $rawRole = \method_exists($entity, 'getDocRoleValue')
            ? $entity->getDocRoleValue()
            : (\method_exists($entity, 'getDocRole')
                ? $entity->getDocRole()
                : null);

        return self::create(
            uuid: $uuid === null ? null : (string) $uuid,
            sourceStoreId: $sourceStoreId === null ? null : (int) $sourceStoreId,
            docRole: match (true) {
                $rawRole instanceof DocRole => $rawRole,
                $rawRole === null           => DocRole::LOCAL,
                default                     => DocRole::fromValue((string) $rawRole),
            },
        );
    }

    /**
     * Identitas untuk record baru yang ditulis di store ini.
     */
    public static function local(?int $sourceStoreId = null, ?string $uuid = null): self
    {
        return self::create(uuid: $uuid, sourceStoreId: $sourceStoreId, docRole: DocRole::LOCAL);
    }

    /**
     * Identitas untuk record hasil apply dari sync — salinan read-only untuk user.
     */
    public static function copy(string $uuid, ?int $sourceStoreId): self
    {
        return self::create(uuid: $uuid, sourceStoreId: $sourceStoreId, docRole: DocRole::COPY);
    }

    /**
     * Identitas untuk record hasil apply yang masih boleh diteruskan lagi.
     */
    public static function interstore(string $uuid, ?int $sourceStoreId): self
    {
        return self::create(uuid: $uuid, sourceStoreId: $sourceStoreId, docRole: DocRole::INTERSTORE);
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getSourceStoreId(): ?int
    {
        return $this->sourceStoreId;
    }

    public function getDocRole(): DocRole
    {
        return $this->docRole;
    }

    public function isLocal(): bool
    {
        return $this->docRole === DocRole::LOCAL;
    }

    public function isCopy(): bool
    {
        return $this->docRole === DocRole::COPY;
    }

    public function isInterstore(): bool
    {
        return $this->docRole === DocRole::INTERSTORE;
    }

    /**
     * Hanya record LOCAL yang boleh diubah oleh user; COPY/INTERSTORE
     * hanya boleh ditulis oleh sync applier.
     */
    public function isEditableByUser(): bool
    {
        return $this->isLocal();
    }

    /**
     * Apakah record ini ditulis oleh store $storeId — dipakai applier
     * untuk skip record yang berasal dari diri sendiri (anti echo-loop).
     */
    public function isOwnedBy(?int $storeId): bool
    {
        return $storeId !== null && $this->sourceStoreId === $storeId;
    }

    public function withDocRole(DocRole $docRole): self
    {
        return new self(
            uuid: $this->uuid,
            sourceStoreId: $this->sourceStoreId,
            docRole: $docRole,
        );
    }

    public function toArray(): array
    {
        return [
            self::fieldUuid()          => $this->uuid,
            self::fieldSourceStoreId() => $this->sourceStoreId,
            self::fieldDocRole()       => $this->docRole->value,
        ];
    }

    public function toDbArray(): array
    {
        return $this->toArray();
    }

    public function equals(self $other): bool
    {
        return $this->uuid === $other->uuid
            && $this->sourceStoreId === $other->sourceStoreId
            && $this->docRole === $other->docRole;
    }

    /**
     * Generate uuid v4 (random). Dipakai saat create record baru —
     * tidak bergantung pada library eksternal.
     */
    public static function newUuid(): string
    {
        $bytes = \random_bytes(16);
        $bytes[6] = \chr((\ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = \chr((\ord($bytes[8]) & 0x3f) | 0x80);

        return \vsprintf('%s%s-%s-%s-%s-%s%s%s', \str_split(\bin2hex($bytes), 4));
    }

    public static function isValidUuid(string $uuid): bool
    {
        return \preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $uuid) === 1;
    }
}
