<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Infrastructure\Security\Actor;
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

        $rule = $this->accessMap[$permissionName] ?? null;
        if ($rule === null) {
            return false;
        }

        // If rule is array, execute each with OR logic
        if (\is_array($rule)) {
            foreach ($rule as $singleRule) {
                if (\is_callable($singleRule)) {
                    $result = (bool) $singleRule($actor);
                    if ($result) {
                        return true; // OR logic - return true if any rule passes
                    }
                }
            }
            return false;
        }

        // If rule is callable, execute it
        if (\is_callable($rule)) {
            return (bool) $rule($actor);
        }

        return false;
    }
}
