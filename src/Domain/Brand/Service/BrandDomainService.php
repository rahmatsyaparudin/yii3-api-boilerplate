<?php

declare(strict_types=1);

namespace App\Domain\Brand\Service;

use App\Domain\Brand\Entity\Brand;
use App\Domain\Brand\Repository\BrandRepositoryInterface;
use App\Domain\Shared\ValueObject\Status;
use App\Shared\ValueObject\Message;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\BusinessRuleException;

/**
 * Brand Domain Service
 * 
 * Pure business logic and domain rules
 */
final class BrandDomainService
{
    public function __construct(
        private BrandRepositoryInterface $repository
    ) {}

    /**
     * Validate unique brand name
     */
    public function validateUniqueName(string $name): void
    {
        if ($this->repository->existsByName($name)) {
            throw new ConflictException(
                translate: new Message(
                    key: 'exists.already_exists', 
                    domain: 'validation',
                    params: [
                        'resource' => 'Brand',
                        'value' => $name
                    ]
                )
            );
        }
    }

    /**
     * Validate name change
     */
    public function validateNameChange(Brand $brand, string $newName): void
    {
        if ($brand->getName() === $newName) {
            return; // No change needed
        }

        $this->validateUniqueName($newName);
    }

    /**
     * Validate status transition
     */
    public function validateStatusTransition(Status $current, Status $new): void
    {
        if (!$current->allowsTransitionTo($new)) {
            throw new BusinessRuleException(
                translate: new Message(
                    key: 'status.invalid_transition',
                    domain: 'validation',
                    params: [
                        'resource' => 'Brand',
                        'from' => $current->value(),
                        'to' => $new->value()
                    ]
                )
            );
        }
    }

    /**
     * Check if brand can be deleted
     */
    public function canDeleteBrand(Brand $brand): void
    {
        if ($brand->isActive() || $brand->isCompleted()) {
            throw new BusinessRuleException(
                translate: new Message(
                    key: 'status.cannot_delete', 
                    domain: 'validation', 
                    params: [
                        'resource' => 'Brand', 
                        'status' => $brand->getStatus()->value()
                    ]
                )
            );
        }
    }

    /**
     * Build change log for audit trail
     */
    public function buildChangeLog(): array
    {
        return [
            'change_log' => [
                'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'created_by' => 'system',
                'updated_at' => null,
                'updated_by' => null,
                'deleted_at' => null,
                'deleted_by' => null,
            ]
        ];
    }
}
