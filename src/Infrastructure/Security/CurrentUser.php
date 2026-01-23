<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

// use App\Infrastructure\Security\Actor;

use App\Domain\Shared\Contract\CurrentUserInterface;
use App\Domain\Shared\Contract\ActorInterface;

final class CurrentUser implements CurrentUserInterface
{
    private ?ActorInterface $actor = null;

    public function getActor(): ActorInterface
    {
        if ($this->actor !== null) {
            return $this->actor;
        }

        return new Actor(username: 'system');
    }

    public function setActor(ActorInterface $actor): void
    {
        $this->actor = $actor;
    }
}
