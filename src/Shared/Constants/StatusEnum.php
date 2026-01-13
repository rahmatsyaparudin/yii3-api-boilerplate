<?php

declare(strict_types=1);

namespace App\Shared\Constants;

enum StatusEnum: int
{
    /**
     * Defines the available status values used across the application.
     *
     * Each case represents a distinct lifecycle state for a given entity,
     * such as a record, resource, or process. These values are typically
     * used to control business logic, validation, and UI behavior based on
     * the current status.
     *
     * Example usage:
     * ```php
     * // Set an initial status
     * $status = StatusEnum::DRAFT;
     *
     * // Check if an entity is active
     * if ($status === StatusEnum::ACTIVE) {
     *     // Run logic for active items
     * }
     *
     * // Use a status value in persistence
     * $storedValue = StatusEnum::APPROVED->value; // 6
     * ```
     */
    case INACTIVE    = 0;
    case ACTIVE      = 1;
    case DRAFT       = 2;
    case COMPLETED   = 3;
    case DELETED     = 4;
    case MAINTENANCE = 5;
    case APPROVED    = 6;
    case REJECTED    = 7;

    /**
     * Returns a human-readable label for the status.
     *
     * Example usage:
     * ```php
     * $status = StatusEnum::ACTIVE;
     * echo $status->label(); // Output: "Active"
     *
     * $status = StatusEnum::DRAFT;
     * echo $status->label(); // Output: "Draft"
     * ```
     *
     * @return string The display label for this status
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
     * Returns an associative array of all status values mapped to their labels.
     *
     * Example usage:
     * ```php
     * $statuses = StatusEnum::list();
     * // Result:
     * // [
     * //     0 => 'Inactive',
     * //     1 => 'Active',
     * //     2 => 'Draft',
     * //     3 => 'Completed',
     * //     4 => 'Deleted',
     * //     5 => 'Maintenance',
     * //     6 => 'Approved',
     * //     7 => 'Rejected',
     * // ]
     * ```
     *
     * @return array<int, string> Array with status values as keys and labels as values
     */
    public static function list(): array
    {
        $list = [];
        foreach (self::cases() as $status) {
            $list[$status->value] = $status->label();
        }

        return $list;
    }

    /**
     * Returns an array of allowed status transitions from the current status.
     *
     * Each status has a defined set of valid transitions to prevent invalid state changes.
     *
     * Example usage:
     * ```php
     * $status = StatusEnum::DRAFT;
     * $allowed = $status->allowedTransitions();
     * // Result: [StatusEnum::INACTIVE, StatusEnum::ACTIVE, StatusEnum::DELETED, StatusEnum::MAINTENANCE]
     *
     * $status = StatusEnum::ACTIVE;
     * $allowed = $status->allowedTransitions();
     * // Result: [StatusEnum::COMPLETED, StatusEnum::APPROVED, StatusEnum::REJECTED]
     *
     * $status = StatusEnum::COMPLETED;
     * $allowed = $status->allowedTransitions();
     * // Result: [] (locked status, no transitions allowed)
     * ```
     *
     * @return self[] Array of StatusEnum cases that are valid transitions from current status
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT       => [self::INACTIVE, self::ACTIVE, self::DELETED, self::MAINTENANCE],
            self::ACTIVE      => [self::COMPLETED, self::APPROVED, self::REJECTED],
            self::INACTIVE    => [self::ACTIVE, self::DRAFT, self::DELETED],
            self::MAINTENANCE => [self::INACTIVE, self::ACTIVE, self::DRAFT, self::DELETED],
            self::APPROVED    => [self::COMPLETED, self::APPROVED, self::REJECTED],
            default           => [],
        };
    }

    /**
     * Get allowed transition targets as their integer values.
     *
     * This is a convenience helper that converts the list of allowed
     * transition targets (returned as StatusEnum cases) into a simple
     * array of their underlying integer values.
     *
     * Example:
     * ```php
     * $status = StatusEnum::DRAFT;
     * $targets = $status->allowedTransitionValues();
     * // e.g. [0, 1, 4, 5]
     * ```
     *
     * @return int[] List of allowed target status values
     */
    public function allowedTransitionValues(): array
    {
        return \array_map(
            static fn (self $status) => $status->value,
            $this->allowedTransitions()
        );
    }

    /**
     * Determine whether the current status is considered locked.
     *
     * A locked status is one that should no longer be modified or transitioned
     * to another status. This is typically used to prevent further updates
     * once a process has reached a terminal state.
     *
     * Example usage:
     * ```php
     * $status = StatusEnum::COMPLETED;
     * if ($status->isLocked()) {
     *     // Perform logic when the status cannot be changed anymore
     * }
     *
     * $status = StatusEnum::DRAFT;
     * if (! $status->isLocked()) {
     *     // This status can still be updated or transitioned
     * }
     * ```
     *
     * @return bool true if the status is locked, false otherwise
     */
    public function isLocked(): bool
    {
        return match ($this) {
            self::COMPLETED, self::DELETED, self::REJECTED => true,
            default => false,
        };
    }

    /**
     * Check whether the current status may transition to the given target.
     *
     * The transition is rejected immediately if the current status is locked.
     * Otherwise, the method delegates the decision to {@see self::allowedTransitions()},
     * ensuring a single source of truth for valid state changes.
     *
     * Example:
     * ```php
     * $current = StatusEnum::DRAFT;
     * $canActivate = $current->canUpdateTo(StatusEnum::ACTIVE);    // true
     * $canComplete = $current->canUpdateTo(StatusEnum::COMPLETED); // false
     *
     * $current = StatusEnum::COMPLETED;
     * $canReopen = $current->canUpdateTo(StatusEnum::ACTIVE);      // false (locked)
     * ```
     *
     * @param self $target desired target status
     *
     * @return bool true if a transition to the target status is permitted, false otherwise
     */
    public function canUpdateTo(self $target): bool
    {
        if ($this->isLocked()) {
            return false;
        }

        $map = [
            self::DRAFT => [
                self::INACTIVE,
                self::ACTIVE,
                self::DELETED,
                self::MAINTENANCE,
            ],
            self::ACTIVE => [
                self::COMPLETED,
                self::APPROVED,
                self::REJECTED,
            ],
            self::INACTIVE => [
                self::ACTIVE,
                self::DRAFT,
                self::DELETED,
            ],
            self::MAINTENANCE => [
                self::INACTIVE,
                self::ACTIVE,
                self::DRAFT,
                self::DELETED,
            ],
            self::APPROVED => [
                self::COMPLETED,
                self::APPROVED,
                self::REJECTED,
            ],
        ];

        return \in_array($target, $map[$this] ?? [], true);
    }

    /**
     * Determine whether a transition from one status value to another is allowed.
     *
     * This method takes the integer values of the current and target statuses,
     * converts them to StatusEnum cases, and then checks if the transition
     * between them is valid according to the business rules defined in
     * {@see canUpdateTo()}.
     *
     * If either the current or target value does not correspond to a valid
     * StatusEnum case, the transition is considered not allowed.
     *
     * Example usage:
     * ```php
     * // Valid transition: DRAFT -> ACTIVE
     * $canActivate = StatusEnum::isAllowedTransition(
     *     StatusEnum::DRAFT->value,
     *     StatusEnum::ACTIVE->value
     * ); // true
     *
     * // Invalid transition: DRAFT -> COMPLETED
     * $canComplete = StatusEnum::isAllowedTransition(
     *     StatusEnum::DRAFT->value,
     *     StatusEnum::COMPLETED->value
     * ); // false
     *
     * // Invalid when using unknown status values
     * $isValid = StatusEnum::isAllowedTransition(999, 1); // false
     * ```
     *
     * @param int $current integer value of the current status
     * @param int $target  integer value of the target status
     *
     * @return bool true if the transition is allowed, false otherwise
     */
    public static function isAllowedTransition(int $current, int $target): bool
    {
        $currentStatus = self::tryFrom($current);
        $targetStatus  = self::tryFrom($target);

        if (!$currentStatus || !$targetStatus) {
            return false;
        }

        return $currentStatus->canUpdateTo($targetStatus);
    }

    /**
     * Determine whether the given integer status value represents a locked status.
     *
     * This method attempts to convert the provided integer into a {@see StatusEnum}
     * case. If the value corresponds to a valid status, it will then check whether
     * that status is considered "locked" (i.e. it can no longer be changed).
     * If the value does not map to any known status, the method returns false.
     *
     * Example usage:
     * ```php
     * $isLocked = StatusEnum::isLockedValue(StatusEnum::COMPLETED->value);
     * // true, because COMPLETED is a locked status
     *
     * $isLocked = StatusEnum::isLockedValue(StatusEnum::DRAFT->value);
     * // false, because DRAFT is not locked and can still be changed
     *
     * $isLocked = StatusEnum::isLockedValue(999);
     * // false, because 999 does not correspond to any known status
     * ```
     *
     * @param int $value integer value of the status to check
     *
     * @return bool true if the value corresponds to a locked status, false otherwise
     */
    public static function isLockedValue(int $value): bool
    {
        $status = self::tryFrom($value);

        return $status ? $status->isLocked() : false;
    }
}
