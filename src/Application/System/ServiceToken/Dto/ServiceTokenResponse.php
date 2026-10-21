<?php

declare(strict_types=1);

namespace App\Application\System\ServiceToken\Dto;

use App\Domain\System\ServiceToken\Entity\ServiceToken;

/**
 * Service Token API response.
 *
 * `token` (plaintext) is populated ONLY at issuance/regeneration —
 * it is the one and only time the secret is visible. The stored
 * SHA-256 hash never leaves the domain layer.
 */
final readonly class ServiceTokenResponse
{
    public function __construct(
        public int $id,
        public int $user_id,
        public string $username,
        public string $name,
        public array $abilities,
        public array $whitelist_host,
        public ?string $last_used_at,
        public ?string $expires_at,
        public int $status,
        public array $detail_info,
        public ?string $created_at,
        public ?string $token = null,
    ) {
    }

    public static function fromEntity(ServiceToken $entity, ?string $plainToken = null): self
    {
        return new self(
            id: $entity->getId(),
            user_id: $entity->getUserId(),
            username: $entity->getUsername(),
            name: $entity->getName(),
            abilities: $entity->getAbilities(),
            whitelist_host: $entity->getWhitelistHost(),
            last_used_at: $entity->getLastUsedAt(),
            expires_at: $entity->getExpiresAt(),
            status: $entity->getStatus()->value(),
            detail_info: $entity->getDetailInfo()->toArray(),
            created_at: $entity->getCreatedAt(),
            token: $plainToken,
        );
    }

    public function toArray(): array
    {
        $data = \get_object_vars($this);

        // Plaintext token only exists in create/regenerate responses.
        if ($data['token'] === null) {
            unset($data['token']);
        }

        return $data;
    }
}
