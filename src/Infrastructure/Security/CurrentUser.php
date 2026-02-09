<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

// Infrastructure Layer
use App\Infrastructure\Security\Actor;

// Domain Layer
use App\Domain\Shared\Contract\CurrentUserInterface;
use App\Domain\Shared\Contract\ActorInterface;

final class CurrentUser implements CurrentUserInterface
{
    private ?ActorInterface $actor = null;

    public function __construct(
        private bool $allowGodMode = false,
    ) {}

    public function getActor(): ActorInterface
    {
        if ($this->actor !== null) {
            return $this->actor;
        }

        return new Actor(username: 'system', allowGodMode: $this->allowGodMode);
    }

    public function setActor(ActorInterface $actor): void
    {
        if ($actor instanceof Actor) {
            $this->actor = new Actor(
                id: $actor->id,
                username: $actor->username,
                dept: $actor->dept,
                roles: $this->extractRoles($actor),
                allowGodMode: $this->allowGodMode
            );
            return;
        }

        $this->actor = $actor;
    }

    private function extractRoles(Actor $actor): array
    {
        $reflection = new \ReflectionClass($actor);
        $property = $reflection->getProperty('roles');
        $property->setAccessible(true);
        return $property->getValue($actor);
    }
}
