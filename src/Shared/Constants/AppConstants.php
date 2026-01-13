<?php

declare(strict_types=1);

namespace App\Shared\Constants;

/**
 * Generic application constants (sync/locking, patterns, common filters).
 */
final class AppConstants
{
    // -------------------------------------------------------------------------
    // Synchronization / Locking Constants
    // -------------------------------------------------------------------------
    public const OPTIMISTIC_LOCK = 'lock_version';
    public const SYNC_MONGODB    = 'sync_mdb';
    public const SYNC_MASTER     = 'sync_master';
    public const SYNC_SLAVE      = 'sync_slave';
    public const SLAVE_ID        = 'slave_id';
    public const MASTER_ID       = 'master_id';

    // -------------------------------------------------------------------------
    // Validation Patterns
    // -------------------------------------------------------------------------
    public const DECIMAL_PATTERN = '/^\d+(\.\d{1,2})?$/';

    // -------------------------------------------------------------------------
    // Filtered Statuses
    // -------------------------------------------------------------------------
    /**
     * Query condition for "status is not deleted".
     *
     * @return array{0:string,1:string,2:int}
     */
    public static function statusNotDeleted(): array
    {
        return ['<>', 'status', StatusEnum::DELETED->value];
    }
}
