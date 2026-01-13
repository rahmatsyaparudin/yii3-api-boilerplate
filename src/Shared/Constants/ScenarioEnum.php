<?php

declare(strict_types=1);

namespace App\Shared\Constants;

enum ScenarioEnum: string
{
    /**
     * Defines the available processing scenarios used throughout the application.
     *
     * Each case represents a specific context or flow in which an operation
     * can be executed (for example, creating a record, updating it, or viewing
     * its details). These values are typically used to drive conditional logic,
     * authorization checks, and UI behavior based on the current scenario.
     *
     * Example usage:
     * ```php
     * // Getting a specific scenario
     * $scenario = ScenarioEnum::CREATE;
     *
     * // Using the scenario in a condition
     * if ($scenario === ScenarioEnum::UPDATE) {
     *     // Execute update-specific logic
     * }
     *
     * // Passing a scenario to a service method
     * $service->handleRequest($data, ScenarioEnum::DELETE);
     * ```
     */
    case DEFAULT      = 'default';
    case CREATE       = 'create';
    case UPDATE       = 'update';
    case DELETE       = 'delete';
    case DRAFT        = 'draft';
    case VIEW         = 'view';
    case COMPLETED    = 'completed';
    case RECEIVE      = 'receive';
    case RECEIVE_ITEM = 'receiveItem';
    case REJECT       = 'reject';
    case REJECT_ITEM  = 'rejectItem';
    case APPROVE      = 'approve';
    case DETAIL       = 'detail';

    /**
     * Returns a human-readable label for the scenario.
     *
     * This method maps each {@see ScenarioEnum} case to a descriptive
     * label that can be safely displayed in a user interface, logs,
     * or API responses.
     *
     * Example usage:
     * ```php
     * $scenario = ScenarioEnum::CREATE;
     * echo $scenario->label(); // Output: "Create"
     *
     * $scenario = ScenarioEnum::RECEIVE_ITEM;
     * echo $scenario->label(); // Output: "Receive Item"
     * ```
     *
     * @return string the display label for this scenario
     */
    public function label(): string
    {
        return match ($this) {
            self::DEFAULT      => 'Default',
            self::CREATE       => 'Create',
            self::UPDATE       => 'Update',
            self::DELETE       => 'Delete',
            self::DRAFT        => 'Draft',
            self::VIEW         => 'View',
            self::COMPLETED    => 'Completed',
            self::RECEIVE      => 'Receive',
            self::RECEIVE_ITEM => 'Receive Item',
            self::REJECT       => 'Reject',
            self::REJECT_ITEM  => 'Reject Item',
            self::APPROVE      => 'Approve',
            self::DETAIL       => 'Detail',
        };
    }

    /**
     * Returns an associative array of all scenarios mapped to their labels.
     *
     * This method iterates over every {@see ScenarioEnum} case and builds an
     * array where the keys are the enum string values (e.g. "create", "update")
     * and the values are their corresponding human‑readable labels as returned
     * by {@see ScenarioEnum::label()}.
     *
     * Example usage:
     * ```php
     * $scenarios = ScenarioEnum::list();
     * // Result (example):
     * // [
     * //     'default'     => 'Default',
     * //     'create'      => 'Create',
     * //     'update'      => 'Update',
     * //     'delete'      => 'Delete',
     * //     'draft'       => 'Draft',
     * //     'view'        => 'View',
     * //     'completed'   => 'Completed',
     * //     'receive'     => 'Receive',
     * //     'receiveItem' => 'Receive Item',
     * //     'reject'      => 'Reject',
     * //     'rejectItem'  => 'Reject Item',
     * //     'approve'     => 'Approve',
     * //     'detail'      => 'Detail',
     * // ]
     * ```
     *
     * @return array<string, string> array with scenario values as keys and labels as values
     */
    public static function list(): array
    {
        $list = [];
        foreach (self::cases() as $scenario) {
            $list[$scenario->value] = $scenario->label();
        }

        return $list;
    }

    /**
     * Returns scenarios that allow update operations.
     *
     * This method centralizes the list of {@see ScenarioEnum} cases that are
     * considered valid for performing update-related actions. It can be used
     * to check whether a given scenario supports modifications, deletions, or
     * other update flows in the application.
     *
     * Example usage:
     * ```php
     * // Get all scenarios that allow updates
     * $updatableScenarios = ScenarioEnum::updateList();
     *
     * // Check if a specific scenario is in the update list
     * $scenario = ScenarioEnum::UPDATE;
     * if (in_array($scenario, $updatableScenarios, true)) {
     *     // Perform update logic
     * }
     * ```
     *
     * @return self[] list of scenarios that allow update operations
     */
    public static function updateList(): array
    {
        return [
            self::UPDATE,
            self::DELETE,
        ];
    }

    /**
     * Get update-allowed scenarios as their string values.
     *
     * This method converts the list of scenarios that allow update operations
     * (returned as {@see ScenarioEnum} cases from {@see self::updateList()})
     * into a simple array of their underlying string values. It is useful
     * when you need to work with raw scenario identifiers, for example when
     * validating input or building queries.
     *
     * Example usage:
     * ```php
     * $values = ScenarioEnum::updateValues();
     * // e.g. ['update', 'delete']
     *
     * if (in_array('update', $values, true)) {
     *     // 'update' is an allowed scenario for update operations
     * }
     * ```
     *
     * @return string[] list of scenario values that allow update operations
     */
    public static function updateValues(): array
    {
        return \array_map(
            static fn (self $scenario) => $scenario->value,
            self::updateList()
        );
    }

    /**
     * Determine whether the current scenario allows update operations.
     *
     * This method checks if the current {@see ScenarioEnum} case is included
     * in the list of scenarios that are permitted to perform update-related
     * actions, as defined by {@see self::updateList()}.
     *
     * Example usage:
     * ```php
     * $scenario = ScenarioEnum::UPDATE;
     * if ($scenario->isUpdatable()) {
     *     // Perform update logic for this scenario
     * }
     *
     * $scenario = ScenarioEnum::VIEW;
     * if (! $scenario->isUpdatable()) {
     *     // Updates are not allowed for this scenario
     * }
     * ```
     *
     * @return bool true if the scenario is allowed to perform update operations, false otherwise
     */
    public function isUpdatable(): bool
    {
        return \in_array($this, self::updateList(), true);
    }
}
