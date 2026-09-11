<?php

declare(strict_types=1);

namespace App\Infrastructure\Core\Security;

// Infrastructure Layer

// Vendor Layer
use Yiisoft\Access\AccessCheckerInterface;

final class AccessChecker implements AccessCheckerInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private array $accessMap
    ) {
    }

    public function userHasPermission(
        \Stringable|string|int|null $userId,
        string $permissionName,
        array $parameters = []
    ): bool {
        // Ignore $userId and use the current actor from CurrentUser
        $actor = $this->currentUser->getActor();

        if ($actor === null) {
            return false;
        }

        // Global wildcard / god mode
        $wildcard = $this->accessMap['*'] ?? null;
        if ($wildcard !== null) {
            $allowed = $this->evaluateRule($wildcard, $actor);
            if ($allowed === true) {
                return true;
            }
        }

        $rule = $this->accessMap[$permissionName] ?? null;
        if ($rule === null) {
            return false;
        }

        return $this->evaluateRule($rule, $actor);
    }

    private function evaluateRule(mixed $rule, Actor $actor): bool
    {
        if (\is_bool($rule)) {
            return $rule;
        }

        if (\is_callable($rule)) {
            return (bool) $rule($actor);
        }

        if (\is_array($rule)) {
            foreach ($rule as $singleRule) {
                if (\is_callable($singleRule) && $singleRule($actor)) {
                    return true;
                }
            }
        }

        return false;
    }
}
