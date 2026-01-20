<?php

declare(strict_types=1);

namespace App\Domain\Brand\Application;

use App\Domain\Brand\Entity\Brand;
use App\Domain\Brand\Repository\BrandRepositoryInterface;
use App\Shared\Validation\UniqueFieldValidator;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\ValidationException;
use App\Shared\Request\RawParams;
use App\Domain\Shared\Trait\EntityValidationTrait;
use App\Domain\Shared\ValueObject\Status;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Application Layer Business Validation
 * 
 * Validasi business logic yang tidak masuk ke entity
 * seperti cross-entity validation atau complex business rules
 */
final class BrandValidator
{
    use EntityValidationTrait;

    const RESOURCE = 'Brand';

    public function __construct(
        private BrandRepositoryInterface $repository,
        private UniqueFieldValidator $uniqueFieldValidator,
        private TranslatorInterface $translator
    ) {
    }

    // ====== PUBLIC VALIDATION METHODS ======

    /**
     * Validasi business rules untuk brand creation
     */
    public function validateForCreation(RawParams $data): void
    {
        // Status validation
        if ($data->status !== null) {
            $status = Status::from($data->status);
            if (!$status->isValidForCreation()) {
                throw new ValidationException(
                    errors: [
                        'status' => $this->translator->translate(
                            'status.invalid_on_creation', 
                            [
                                'resource' => self::RESOURCE,
                            ], 
                            'validation'
                        )
                    ]
                );
            }
        }
        
        // Cross-entity validation:
        $this->validateUniqueField(
            data: $data,
            uniqueFieldValidator: $this->uniqueFieldValidator,
            repository: $this->repository,
            field: 'name'
        );
        
        // Business constraints:
        $this->validateBusinessConstraints(
            data: $data
        );
    }

    /**
     * Validasi business rules untuk brand update
     */
    public function validateForUpdate(RawParams $data): void
    {
        // Get existing brand for validation
        $brand = $this->repository->findById((int) $data->id);
        if ($brand === null) {
            throw new NotFoundException(
                translate: [
                    'key' => 'resource.not_found',
                    'params' => [
                        'resource' => self::RESOURCE,
                        'field' => 'ID',
                        'value' => $data->id
                    ]
                ]
            );
        }

        // Unique field validation
        if ($data->name && $data->name !== $brand->name()) {
            $this->validateUniqueField(
                data: $data,
                uniqueFieldValidator: $this->uniqueFieldValidator,
                repository: $this->repository,
                field: 'name',
                excludeId: $brand->id()
            );
        }

        // Business rules validation - use trait method
        $this->validateStatusForUpdate($brand->toArray(), $data, self::RESOURCE);
    }

    /**
     * Validasi business rules untuk brand search
     */
    public function validateForSearch(RawParams $data): void
    {
        // Search-specific validation
        // TODO: Implement search-specific validation
        // - Validate search parameters
        // - Check search permissions
        // - Validate filter combinations
    }

    /**
     * Validasi business rules untuk brand deletion
     */
    public function validateForDeletion(Brand $brand): void
    {
        if (!$brand->canBeDeleted()) {
            throw new ValidationException(
                errors: [
                    'brand' => $this->translator->translate(
                        'cannot_delete_active', 
                        [
                            'resource' => self::RESOURCE
                        ], 
                        'validation'
                    )
                ]
            );
        }

        // Check dependencies
        $this->checkDependencies(
            entity: $brand
        );
    }

    protected function getValidationResource(): string
    {
        return self::RESOURCE;
    }

    protected function validateBusinessConstraints(RawParams $data): void
    {
        // Brand-specific business constraints
        // TODO: Implement brand-specific constraints
        // - Check if brand category exists
        // - Check if brand meets requirements
        // - Check external API validation
    }

    protected function checkDependencies(object $entity): void
    {
        // Brand-specific dependency checking
        // TODO: Check if brand has dependencies
        // - Products using this brand
        // - Orders with this brand
        // - Other references
    }
}
