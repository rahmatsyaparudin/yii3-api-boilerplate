<?php

declare(strict_types=1);

namespace App\Domain\Shared\Trait;

use App\Shared\Request\RawParams;
use App\Shared\Validation\UniqueFieldValidator;
use App\Domain\Brand\Repository\BrandRepositoryInterface;

/**
 * Entity Validation Trait
 * 
 * Provides common validation methods that can be used across
 * different domain validators for consistent validation patterns.
 */
trait EntityValidationTrait
{
    /**
     * Validate unique field across entities
     */
    protected function validateUniqueField(
        RawParams $data,
        UniqueFieldValidator $uniqueFieldValidator,
        BrandRepositoryInterface $repository,
        string $field = 'name',
        ?int $excludeId = null
    ): void {
        if ($data->$field) {
            $uniqueFieldValidator->validateUniqueField(
                field: $field,
                value: $data->$field,
                finder: fn(string $value) => $repository->findByName($value),
                resource: $this->getValidationResource(),
                excludeId: $excludeId
            );
        }
    }

    /**
     * Validate business constraints (template method)
     * Override in concrete validators for specific business rules
     */
    protected function validateBusinessConstraints(RawParams $data): void
    {
        // Template method - override in concrete validators
        // Add specific business constraints validation here
    }

    /**
     * Validate business rules for update (template method)
     * Override in concrete validators for specific update rules
     */
    protected function validateBusinessRulesForUpdate(object $entity, RawParams $data): void
    {
        // Template method - override in concrete validators
        // Add specific update business rules here
    }

    /**
     * Get validation resource name (template method)
     * Override in concrete validators to return entity name
     */
    protected function getValidationResource(): string
    {
        // Template method - override in concrete validators
        return 'Entity';
    }

    /**
     * Check dependencies (template method)
     * Override in concrete validators to check entity dependencies
     */
    protected function checkDependencies(object $entity): void
    {
        // Template method - override in concrete validators
        // Add dependency checking logic here
    }
}
