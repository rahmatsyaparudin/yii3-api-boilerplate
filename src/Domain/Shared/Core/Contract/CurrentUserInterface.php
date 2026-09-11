<?php

declare(strict_types=1);

namespace App\Domain\Shared\Core\Contract;

interface CurrentUserInterface
{
    public function getActor(): ActorInterface;
}
