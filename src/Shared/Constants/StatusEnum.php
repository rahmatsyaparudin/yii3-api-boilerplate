<?php

declare(strict_types=1);

namespace App\Shared\Constants;

/**
 * Status enumeration for domain entities.
 * 
 * This enum defines the possible status values that can be used across
 * the application. It should only contain the raw values and basic
 * metadata, not business logic.
 */
enum StatusEnum: int
{
    case INACTIVE    = 0;
    case ACTIVE      = 1;
    case DRAFT       = 2;
    case COMPLETED   = 3;
    case DELETED     = 4;
    case MAINTENANCE = 5;
    case APPROVED    = 6;
    case REJECTED    = 7;

    /**
     * Get human-readable label for the status
     */
    public function label(): string
    {
        return match ($this) {
            self::INACTIVE    => 'Inactive',
            self::ACTIVE      => 'Active',
            self::DRAFT       => 'Draft',
            self::COMPLETED   => 'Completed',
            self::DELETED     => 'Deleted',
            self::MAINTENANCE => 'Maintenance',
            self::APPROVED    => 'Approved',
            self::REJECTED    => 'Rejected',
        };
    }

    /**
     * Get all statuses as associative array
     */
    public static function list(): array
    {
        $list = [];
        foreach (self::cases() as $status) {
            $list[$status->value] = $status->label();
        }
        return $list;
    }
}
