<?php

declare(strict_types=1);

namespace App\Domain\Common\Audit;

use App\Infrastructure\Security\CurrentUser;
use Psr\Clock\ClockInterface;

final class ChangeLogFactory
{
    public function __construct(
        private readonly ClockInterface $clock,
        private readonly CurrentUser $currentUser,
    ) {
    }

    /**
     * Create new ChangeLog for entity creation.
     */
    public function create(?Actor $actor = null): ChangeLog
    {
        $actor ??= $this->currentUser->getActor();

        return ChangeLog::create(
            actor: $actor,
            now: $this->clock->now()
        );
    }

    /**
     * Mark ChangeLog as updated.
     */
    public function markUpdated(ChangeLog $changeLog, ?Actor $actor = null): ChangeLog
    {
        $actor ??= $this->currentUser->getActor();

        return $changeLog->markUpdated(
            actor: $actor,
            now: $this->clock->now()
        );
    }

    /**
     * Mark ChangeLog as deleted (soft delete).
     */
    public function markDeleted(ChangeLog $changeLog, ?Actor $actor = null): ChangeLog
    {
        $actor ??= $this->currentUser->getActor();

        return $changeLog->markDeleted(
            actor: $actor,
            now: $this->clock->now()
        );
    }
}
