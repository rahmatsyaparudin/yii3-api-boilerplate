<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Common\Audit\Actor;
use App\Shared\Exception\UnauthorizedException;

final class ActorProvider
{
    public function fromToken(object $claims): Actor
    {
        // Handle custom SSO structure with user object
        if (isset($claims->user) && \is_object($claims->user)) {
            $user = $claims->user;

            if (!isset($user->username)) {
                throw new UnauthorizedException(
                    translate: [
                        'key' => 'auth.missing_claim',
                        'params' => ['claim' => 'user.username']
                    ]
                );
            }

            return new Actor(
                id: (int) ($user->id ?? 0),
                username: (string) $user->username,
                dept: (string) ($user->dept ?? ''),
                roles: $this->normalizeRoles($user->roles ?? [])
            );
        }

        // Fallback to standard preferred_username claim
        if (isset($claims->preferred_username)) {
            return new Actor(username: (string) $claims->preferred_username);
        }

        throw new UnauthorizedException(
            translate: [
                'key' => 'auth.missing_claim',
                'params' => ['claim' => 'username']
            ]
        );
    }

    /**
     * Recursively normalize role payload (stdClass/array) into a PHP array.
     */
    private function normalizeRoles(mixed $roles): array
    {
        if (\is_array($roles)) {
            $normalized = [];
            foreach ($roles as $key => $value) {
                $normalized[$key] = \is_array($value) || \is_object($value)
                    ? $this->normalizeRoles($value)
                    : $value;
            }

            return $normalized;
        }

        if (\is_object($roles)) {
            $normalized = [];
            foreach (\get_object_vars($roles) as $key => $value) {
                $normalized[$key] = \is_array($value) || \is_object($value)
                    ? $this->normalizeRoles($value)
                    : $value;
            }

            return $normalized;
        }

        return [];
    }
}
