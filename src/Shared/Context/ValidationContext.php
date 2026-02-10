<?php

declare(strict_types=1);

namespace App\Shared\Context;

use App\Shared\Validation\ValidationContextInterface;

final class ValidationContext implements ValidationContextInterface
{
    /**
     * Validation context constants for different validation scenarios.
     * 
     * Used to determine validation rules based on the operation context:
     * - CREATE: Validation for entity creation
     * - UPDATE: Validation for entity updates
     * - DELETE: Validation for entity deletion
     * - RESTORE: Validation for entity restoration
     * - SEARCH: Validation for search/filter operations
     * - APPROVE: Validation for approval operations
     * - REJECT: Validation for rejection operations
     * 
     * // Custom context examples
     * public const CREATE_DO = 'create_do';
     * public const UPDATE_DO = 'update_do';
     * public const DELETE_DO = 'delete_do';
     * public const APPROVE_DO = 'approve_do';
     * public const REJECT_DO = 'reject_do';
     */ 
    
}