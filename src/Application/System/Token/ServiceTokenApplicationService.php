<?php

declare(strict_types=1);

namespace App\Application\System\Token;

// Application Layer
use App\Application\Shared\Core\Factory\DetailInfoFactory;
// Domain Layer
use App\Domain\Shared\Core\Contract\ActorInterface;
use App\Domain\Shared\Core\Contract\DateTimeProviderInterface;
use App\Domain\Shared\Core\ValueObject\ResourceStatus;
use App\Domain\System\Token\Entity\ServiceToken;
use App\Domain\System\Token\Repository\ServiceTokenRepositoryInterface;
// Infrastructure Layer
use App\Application\System\Token\Dto\ServiceTokenResponse;
use App\Infrastructure\Core\Security\Actor;
use App\Infrastructure\Core\Security\CurrentUser;
// Shared Layer
use App\Shared\Core\Dto\PaginatedResult;
use App\Shared\Core\Dto\SearchCriteria;
use App\Shared\Core\Exception\ConflictException;
use App\Shared\Core\Exception\NotFoundException;
use App\Shared\Core\ValueObject\Message;
// Vendor Layer
use Yiisoft\Db\Exception\Exception as DbException;

/**
 * Service Token Application Service.
 *
 * Issues and manages long-lived opaque bearer tokens for external
 * services — the SSO-free alternative to JWT. Secrets are generated
 * here (`bin2hex(random_bytes(32))`); only the SHA-256 hash is
 * persisted, the plaintext is returned once and never again.
 *
 * `authenticate()` resolves a bearer into the issuing user's Actor
 * (SSO-style): downstream permissions, audit and DetailInfo changelog
 * all run as that user. Every failure returns `null` so the auth
 * middleware can fall back to JWT — never throws.
 */
final class ServiceTokenApplicationService
{
    public function __construct(
        private ServiceTokenRepositoryInterface $repository,
        private DetailInfoFactory $detailInfoFactory,
        private CurrentUser $currentUser,
        private DateTimeProviderInterface $dateTime,
    ) {
    }

    public function getResource(): string
    {
        return ServiceToken::RESOURCE;
    }

    private function getEntityById(int $id): ServiceToken
    {
        $data = $this->repository->findById(id: $id);

        if ($data === null) {
            throw new NotFoundException(translate: Message::create(key: 'resource.not_found', params: ['resource' => $this->getResource(), 'field' => 'id', 'value' => $id]));
        }

        return $data;
    }

    public function list(SearchCriteria $criteria): PaginatedResult
    {
        return $this->repository->list(
            criteria: $criteria
        );
    }

    public function view(int $id): ServiceTokenResponse
    {
        $data = $this->getEntityById(
            id: $id
        );

        return ServiceTokenResponse::fromEntity(
            entity: $data
        );
    }

    /**
     * Issue a new service token.
     *
     * `user_id` is taken from the current actor (the issuer) — never
     * from input. The plaintext secret is returned once inside the
     * response; only its SHA-256 hash is stored.
     */
    public function create(array $input): ServiceTokenResponse
    {
        $username = \trim((string) ($input['username'] ?? ''));
        $name     = \trim((string) ($input['name'] ?? ''));

        $this->guardUsernameUnique(username: $username);

        [$plainToken, $tokenHash] = self::generateToken();

        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: []
            )
            ->build();

        $entity = ServiceToken::create(
            userId: $this->currentUser->getActor()->getId(),
            username: $username,
            name: $name,
            tokenHash: $tokenHash,
            abilities: self::normalizeList($input['abilities'] ?? null),
            whitelistHost: self::normalizeList($input['whitelist_host'] ?? null),
            expiresAt: self::normalizeExpiresAt($input['expires_at'] ?? null),
            detailInfo: $detailInfo,
            createdAt: $this->dateTime->database(),
        );

        return ServiceTokenResponse::fromEntity(
            entity: $this->repository->insert(
                serviceToken: $entity
            ),
            plainToken: $plainToken
        );
    }

    /**
     * Update a service token.
     *
     * - `username` may be renamed; stays unique excluding self (409).
     * - `regenerate: true` rotates the secret — the old bearer dies
     *   instantly and the new plaintext is returned once.
     * - `expires_at` empty does NOT clear the expiry.
     * - `status`: 1 active (re-activate), 0 inactive, 4 deleted.
     */
    public function update(int $id, array $input): ServiceTokenResponse
    {
        $data = $this->getEntityById(
            id: $id
        );

        $username = isset($input['username']) ? \trim((string) $input['username']) : null;

        if ($username !== null && $username !== $data->getUsername()) {
            $this->guardUsernameUnique(username: $username, ignoreId: $data->getId());
        }

        $data->update(
            username: $username,
            name: isset($input['name']) ? \trim((string) $input['name']) : null,
            abilities: \array_key_exists('abilities', $input) ? self::normalizeList($input['abilities']) : null,
            whitelistHost: \array_key_exists('whitelist_host', $input) ? self::normalizeList($input['whitelist_host']) : null,
            expiresAt: !empty($input['expires_at']) ? self::normalizeExpiresAt($input['expires_at']) : null,
        );

        $data->applyStatus(
            newStatus: ResourceStatus::tryFrom($input['status'] ?? null)
        );

        $plainToken = null;

        if (\filter_var($input['regenerate'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            [$plainToken, $tokenHash] = self::generateToken();
            $data->rotateToken(
                tokenHash: $tokenHash
            );
        }

        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $data->getDetailInfo(),
                payload: [],
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        return ServiceTokenResponse::fromEntity(
            entity: $this->repository->update(
                serviceToken: $data
            ),
            plainToken: $plainToken
        );
    }

    /**
     * Revoke (soft delete). Takes effect on the very next request.
     */
    public function delete(int $id): ServiceTokenResponse
    {
        $data = $this->getEntityById(
            id: $id
        );

        $detailInfo = $this->detailInfoFactory
            ->delete(
                detailInfo: $data->getDetailInfo(),
                payload: [],
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        $data->markAsDeleted();

        return ServiceTokenResponse::fromEntity(
            entity: $this->repository->update(
                serviceToken: $data
            )
        );
    }

    /**
     * Authenticate an opaque bearer token.
     *
     * Returns the issuing user's Actor on success — or `null` for ANY
     * failure (unknown/expired/revoked token, host not whitelisted,
     * missing `service_tokens` table, DB error), letting the auth
     * middleware fall back to JWT.
     */
    public function authenticate(string $plainToken, ?string $clientHost): ?ActorInterface
    {
        if ($plainToken === '') {
            return null;
        }

        try {
            $entity = $this->repository->findActiveByTokenHash(
                tokenHash: \hash('sha256', $plainToken)
            );

            if ($entity === null) {
                return null;
            }

            if ($entity->isExpired(now: $this->dateTime->database())) {
                return null;
            }

            if (!self::hostAllowed(whitelist: $entity->getWhitelistHost(), host: $clientHost)) {
                return null;
            }

            $this->repository->touchLastUsed(
                id: $entity->getId(),
                lastUsedAt: $this->dateTime->database()
            );
        } catch (DbException) {
            // service_tokens not migrated / DB unreachable — degrade to JWT.
            return null;
        }

        return new Actor(
            id: $entity->getUserId(),
            username: $entity->getUsername()
        );
    }

    /**
     * @return array{0: string, 1: string} [plaintext, sha256 hash]
     */
    private static function generateToken(): array
    {
        $plain = \bin2hex(\random_bytes(32));

        return [$plain, \hash('sha256', $plain)];
    }

    private function guardUsernameUnique(string $username, ?int $ignoreId = null): void
    {
        if ($this->repository->existsUsername(username: $username, ignoreId: $ignoreId)) {
            throw new ConflictException(
                translate: Message::create(
                    key: 'service_token.duplicate',
                    params: ['username' => $username],
                ),
                errors: [
                    'field' => 'username',
                    'value' => $username,
                ]
            );
        }
    }

    /**
     * Match caller host against whitelist entries: `*` (any), exact,
     * prefix wildcard `192.168.1.*`, suffix wildcard `*.pos.local`.
     * Empty list allows all; unknown host fails closed when the list
     * is non-empty.
     */
    private static function hostAllowed(array $whitelist, ?string $host): bool
    {
        if ($whitelist === []) {
            return true;
        }

        if ($host === null || $host === '') {
            return false;
        }

        foreach ($whitelist as $entry) {
            $entry = \trim((string) $entry);

            if ($entry === '') {
                continue;
            }

            if ($entry === '*') {
                return true;
            }

            if (\str_ends_with($entry, '*') && \str_starts_with($entry, '*') === false) {
                if (\str_starts_with($host, \substr($entry, 0, -1))) {
                    return true;
                }

                continue;
            }

            if (\str_starts_with($entry, '*')) {
                if (\str_ends_with($host, \substr($entry, 1))) {
                    return true;
                }

                continue;
            }

            if ($host === $entry) {
                return true;
            }
        }

        return false;
    }

    /**
     * Accepts array, JSON string, or CSV and normalizes to a list of
     * strings. `null`/empty input yields `[]`.
     */
    private static function normalizeList(mixed $value): array
    {
        if (\is_string($value)) {
            $value = \trim($value);

            if ($value === '') {
                return [];
            }

            if (\str_starts_with($value, '[')) {
                $decoded = \json_decode($value, true);
                $value   = \is_array($decoded) ? $decoded : [];
            } else {
                $value = \explode(',', $value);
            }
        }

        if (!\is_array($value)) {
            return [];
        }

        return \array_values(
            \array_filter(
                \array_map(
                    static fn ($item) => \trim((string) $item),
                    $value
                ),
                static fn ($item) => $item !== ''
            )
        );
    }

    /**
     * Accepts `Y-m-d` or `Y-m-d H:i[:s]`; empty clears to `null`.
     * Date-only input becomes midnight of that day.
     */
    private static function normalizeExpiresAt(mixed $value): ?string
    {
        if (!\is_string($value)) {
            return null;
        }

        $value = \trim($value);

        if ($value === '') {
            return null;
        }

        if (\strlen($value) === 10) {
            return $value . ' 00:00:00';
        }

        if (\strlen($value) === 16) {
            return $value . ':00';
        }

        return $value;
    }
}
