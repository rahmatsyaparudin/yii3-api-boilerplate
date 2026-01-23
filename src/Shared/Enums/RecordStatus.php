<?php

declare(strict_types=1);

namespace App\Shared\Enums;

/**
 * Status enumeration for domain entities.
 * 
 * This enum defines the possible status values that can be used across
 * the application. It should only contain the raw values and basic
 * metadata, not business logic.
 */
enum RecordStatus: int
{
    case INACTIVE    = 0;
    case ACTIVE      = 1;
    case DRAFT       = 2;
    case COMPLETED   = 3;
    case DELETED     = 4;
    case MAINTENANCE = 5;
    case APPROVED    = 6;
    case REJECTED    = 7;

    public const IMMUTABLE_STATUSES = [
        self::ACTIVE->value,
        self::COMPLETED->value,
        self::DELETED->value,
    ];

    /**
     * Allowed status transitions for updates
     */
    public const STATUS_TRANSITION_MAP = [
        self::DRAFT->value => [
            self::DRAFT->value,
            self::INACTIVE->value,
            self::ACTIVE->value,
            self::DELETED->value,
            self::MAINTENANCE->value,
        ],
        self::ACTIVE->value => [
            self::COMPLETED->value,
            self::APPROVED->value,
            self::REJECTED->value,
        ],
        self::INACTIVE->value => [
            self::INACTIVE->value,
            self::ACTIVE->value,
            self::DRAFT->value,
            self::DELETED->value,
        ],
        self::MAINTENANCE->value => [
            self::MAINTENANCE->value,
            self::INACTIVE->value,
            self::ACTIVE->value,
            self::DRAFT->value,
            self::DELETED->value,
        ],
        self::APPROVED->value => [
            self::APPROVED->value,
            self::COMPLETED->value,
            self::REJECTED->value,
        ],
    ];

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

    public static function activeOnlyStates(): array
    {
        return [self::ACTIVE->value];
    }

    public static function draftOnlyStates(): array
    {
        return [self::DRAFT->value];
    }

    public static function list(): array
    {
        return array_reduce(
            self::cases(),
            static function (array $carry, self $status) {
                $carry[$status->value] = $status->label();
                return $carry;
            },
            []
        );
    }

    public static function searchableStates(): array
    {
        $states = [];
        foreach (self::cases() as $status) {
            if ($status !== self::DELETED) {
                $states[] = $status->value;
            }
        }
        return $states;
    }
}
