<?php

declare(strict_types=1);

namespace App\Shared\Enums;

/**
 * Record Status Enumeration
 * 
 * This enum defines the possible status values that can be used across
 * the application for domain entities. It provides type-safe status handling
 * with built-in validation, transitions, and human-readable labels.
 * 
 * @package App\Shared\Enums
 * 
 * @example
 * // Basic usage in entity
 * class User {
 *     public function __construct(
 *         public readonly int $id,
 *         public readonly string $name,
 *         public readonly RecordStatus $status = RecordStatus::ACTIVE
 *     ) {}
 * }
 * 
 * @example
 * // Status validation in service
 * if ($user->status === RecordStatus::DELETED) {
 *     throw new NotFoundException('User not found');
 * }
 * 
 * @example
 * // Status transitions
 * $currentStatus = RecordStatus::DRAFT;
 * $allowedTransitions = RecordStatus::STATUS_TRANSITION_MAP[$currentStatus->value] ?? [];
 * if (!in_array($newStatus->value, $allowedTransitions)) {
 *     throw new BadRequestException('Invalid status transition');
 * }
 * 
 * @example
 * // Query filtering
 * $activeUsers = $repository->findByStatus(RecordStatus::ACTIVE->value);
 * $searchableStates = RecordStatus::searchableStates();
 * $results = $repository->findByStatuses($searchableStates);
 */
enum RecordStatus: int
{
    /**
     * Inactive status - entity exists but is not active
     */
    case INACTIVE = 0;
    
    /**
     * Active status - entity is fully active and operational
     */
    case ACTIVE = 1;
    
    /**
     * Draft status - entity is in draft/preview state
     */
    case DRAFT = 2;
    
    /**
     * Completed status - entity has been completed/finished
     */
    case COMPLETED = 3;
    
    /**
     * Deleted status - entity has been soft-deleted
     */
    case DELETED = 4;
    
    /**
     * Maintenance status - entity is under maintenance
     */
    case MAINTENANCE = 5;
    
    /**
     * Approved status - entity has been approved
     */
    case APPROVED = 6;
    
    /**
     * Rejected status - entity has been rejected
     */
    case REJECTED = 7;

    /**
     * Immutable statuses that cannot be changed once set
     * These statuses are considered final and should not allow further transitions
     */
    public const IMMUTABLE_STATUSES = [
        self::ACTIVE->value,
        self::COMPLETED->value,
        self::DELETED->value,
    ];

    /**
     * Allowed status transitions for updates
     * 
     * This map defines which status transitions are allowed.
     * The key is the current status value, and the value is an array of allowed target statuses.
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
     * 
     * Returns a user-friendly label that can be used in UI displays
     * or API responses.
     * 
     * @return string Human-readable status label
     * 
     * @example
     * echo RecordStatus::ACTIVE->label(); // Output: "Active"
     * echo RecordStatus::DRAFT->label(); // Output: "Draft"
     * 
     * @example
     * // In API response
     * return [
     *     'id' => $user->id,
     *     'status' => $user->status->value,
     *     'status_label' => $user->status->label()
     * ];
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
     * Get array of active status values only
     * 
     * Returns an array containing only the ACTIVE status value.
     * Useful for filtering entities that must be active.
     * 
     * @return array<int> Array containing only active status value
     * 
     * @example
     * // In repository
     * $activeUsers = $repository->findByStatuses(RecordStatus::activeOnlyStates());
     * // Equivalent to: $repository->findByStatuses([1]);
     * 
     * @example
     * // In query builder
     * $query->andWhere(['status' => RecordStatus::activeOnlyStates()]);
     */
    public static function activeOnlyStates(): array
    {
        return [self::ACTIVE->value];
    }

    /**
     * Get array of draft status values only
     * 
     * Returns an array containing only the DRAFT status value.
     * Useful for filtering entities that are in draft state.
     * 
     * @return array<int> Array containing only draft status value
     * 
     * @example
     * // Get all draft entities
     * $draftEntities = $repository->findByStatuses(RecordStatus::draftOnlyStates());
     * 
     * @example
     * // In service validation
     * if (!in_array($entity->status->value, RecordStatus::draftOnlyStates())) {
     *     throw new BadRequestException('Entity must be in draft status');
     * }
     */
    public static function draftOnlyStates(): array
    {
        return [self::DRAFT->value];
    }

    /**
     * Get all statuses as value-label array
     * 
     * Returns an associative array with status values as keys and
     * human-readable labels as values. Useful for dropdown options
     * or API documentation.
     * 
     * @return array<int, string> Status values mapped to labels
     * 
     * @example
     * // For dropdown options
     * $statusOptions = RecordStatus::list();
     * foreach ($statusOptions as $value => $label) {
     *     echo "<option value=\"$value\">$label</option>";
     * }
     * 
     * @example
     * // In API response
     * return [
     *     'data' => $entities,
     *     'status_options' => RecordStatus::list()
     * ];
     */
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

    /**
     * Get searchable status values
     * 
     * Returns an array of status values that can be searched/exposed
     * in public interfaces. Excludes DELETED status to prevent
     * soft-deleted items from appearing in search results.
     * 
     * @return array<int> Array of searchable status values
     * 
     * @example
     * // In repository search
     * $searchableStates = RecordStatus::searchableStates();
     * $results = $repository->findByStatuses($searchableStates);
     * 
     * @example
     * // In search service
     * $criteria = new SearchCriteria(
     *     filter: ['status' => $searchableStates],
     *     page: 1,
     *     pageSize: 10
     * );
     * 
     * @example
     * // Excluding deleted items from public API
     * $publicItems = $repository->findByStatuses(RecordStatus::searchableStates());
     */
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
