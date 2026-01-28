<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Infrastructure\Security\Actor;

class PermissionChecker
{
    private array $rules;

    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }

    public function can(Actor $actor, string $permission): bool
    {
        // If permission is not registered in config, default: deny
        if (!isset($this->rules[$permission])) {
            return false;
        }

        $rule = $this->rules[$permission];

        // Scenario A: Rule is a single Closure (e.g: 'example.view' => $isKasir)
        if (is_callable($rule)) {
            return $rule($actor);
        }

        // Scenario B: Rule is an Array (e.g: 'example.data' => [$isSuperAdmin, $isKasir])
        if (is_array($rule)) {
            foreach ($rule as $check) {
                if (is_callable($check) && $check($actor)) {
                    return true; // If any role matches, allow (OR logic)
                }
            }
        }
        
        // Scenario C: Rule is a pure Boolean (e.g: 'example.index' => true)
        if (is_bool($rule)) {
            return $rule;
        }

        return false;
    }
}