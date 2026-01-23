<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contract;

interface CurrentUserInterface
{
    public function getActor(): ActorInterface;
}