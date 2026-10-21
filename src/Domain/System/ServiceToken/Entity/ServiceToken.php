<?php

declare(strict_types=1);

namespace App\Domain\System\ServiceToken\Entity;

// Domain Layer
use App\Domain\Shared\Core\Concerns\Entity\Descriptive;
use App\Domain\Shared\Core\Concerns\Entity\Stateful;
use App\Domain\Shared\Core\ValueObject\DetailInfo;
use App\Domain\Shared\Core\ValueObject\ResourceStatus;

/**
 * Service Token — long-lived opaque bearer token for external services
 * (POS, scripts, integrations), the SSO-free alternative to JWT.
 *
 * `tokenHash` holds only the SHA-256 of the issued secret; the plaintext
 * is shown once at issuance/regeneration and never persisted. `username`
 * is the service identity (unique across all rows), not the issuer's
 * login name. Soft delete via `status` keeps the audit trail.
 */
final class ServiceToken
{
    use Stateful;
    use Descriptive;

    public const RESOURCE = 'ServiceToken';

    protected function __construct(
        private readonly ?int $id,
        private int $userId,
        private string $username,
        private string $name,
        private string $tokenHash,
        private array $abilities,
        private array $whitelistHost,
        private ?string $lastUsedAt,
        private ?string $expiresAt,
        private ResourceStatus $status,
        private DetailInfo $detailInfo,
        private ?string $createdAt,
    ) {
    }

    public static function getResource(): string
    {
        return self::RESOURCE;
    }

    public static function create(
        int $userId,
        string $username,
        string $name,
        string $tokenHash,
        array $abilities,
        array $whitelistHost,
        ?string $expiresAt,
        DetailInfo $detailInfo,
        string $createdAt,
    ): self {
        $status = ResourceStatus::active();

        self::guardInitialStatus(
            status: $status,
            resource: self::RESOURCE
        );

        return new self(
            id: null,
            userId: $userId,
            username: $username,
            name: $name,
            tokenHash: $tokenHash,
            abilities: $abilities,
            whitelistHost: $whitelistHost,
            lastUsedAt: null,
            expiresAt: $expiresAt,
            status: $status,
            detailInfo: $detailInfo,
            createdAt: $createdAt,
        );
    }

    public static function reconstitute(
        int $id,
        int $userId,
        string $username,
        string $name,
        string $tokenHash,
        array $abilities,
        array $whitelistHost,
        ?string $lastUsedAt,
        ?string $expiresAt,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?string $createdAt,
    ): self {
        return new self(
            id: $id,
            userId: $userId,
            username: $username,
            name: $name,
            tokenHash: $tokenHash,
            abilities: $abilities,
            whitelistHost: $whitelistHost,
            lastUsedAt: $lastUsedAt,
            expiresAt: $expiresAt,
            status: $status,
            detailInfo: $detailInfo,
            createdAt: $createdAt,
        );
    }

    public function update(
        ?string $username = null,
        ?string $name = null,
        ?array $abilities = null,
        ?array $whitelistHost = null,
        ?string $expiresAt = null,
    ): void {
        if ($username !== null) {
            $this->username = $username;
        }

        if ($name !== null) {
            $this->name = $name;
        }

        if ($abilities !== null) {
            $this->abilities = $abilities;
        }

        if ($whitelistHost !== null) {
            $this->whitelistHost = $whitelistHost;
        }

        if ($expiresAt !== null) {
            $this->expiresAt = $expiresAt;
        }
    }

    /**
     * Rotate the secret: store the new hash and reset last_used_at —
     * the previous bearer dies immediately.
     */
    public function rotateToken(string $tokenHash): void
    {
        $this->tokenHash  = $tokenHash;
        $this->lastUsedAt = null;
    }

    public function isExpired(string $now): bool
    {
        return $this->expiresAt !== null && $this->expiresAt <= $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Internal use only (persistence). The hash is never exposed in
     * `toArray()` or API responses.
     */
    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function getAbilities(): array
    {
        return $this->abilities;
    }

    public function getWhitelistHost(): array
    {
        return $this->whitelistHost;
    }

    public function getLastUsedAt(): ?string
    {
        return $this->lastUsedAt;
    }

    public function getExpiresAt(): ?string
    {
        return $this->expiresAt;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id'             => $this->id,
            'user_id'        => $this->userId,
            'username'       => $this->username,
            'name'           => $this->name,
            'abilities'      => $this->abilities,
            'whitelist_host' => $this->whitelistHost,
            'last_used_at'   => $this->lastUsedAt,
            'expires_at'     => $this->expiresAt,
            'status'         => $this->status->value(),
            'detail_info'    => $this->detailInfo->toArray(),
            'created_at'     => $this->createdAt,
        ];
    }
}
