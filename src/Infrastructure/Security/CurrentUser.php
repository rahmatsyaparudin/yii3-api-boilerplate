<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Common\Audit\Actor;

final class CurrentUser
{
    private ?Actor $actor = null;

    public function getActor(): Actor
    {
        // Return cached actor if already set
        if ($this->actor !== null) {
            return $this->actor;
        }

        // Fallback for non-authenticated requests (e.g., console commands)
        return new Actor(username: 'system');
    }

    public function setActor(Actor $actor): void
    {
        $this->actor = $actor;
    }
}
