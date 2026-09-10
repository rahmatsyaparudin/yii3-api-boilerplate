<?php

declare(strict_types=1);

namespace App\Domain\AnotherExample\Service;

// Domain Layer
use App\Domain\AnotherExample\Entity\AnotherExample;
use App\Domain\AnotherExample\Repository\AnotherExampleRepositoryInterface;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\ValueObject\Message;

/**
 * AnotherExample Domain Service
 * 
 * Pure business logic and domain rules
 */
final class AnotherExampleDomainService
{
    use DomainValidator;
    
    public function __construct(
        private AnotherExampleRepositoryInterface $repository
    ) {}

    /**
     * Place AnotherExample-specific business logic here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     * 
     * AnotherExamples:
     * - Complex validation rules
     * - Business calculations
     * - Domain-specific operations
     * - Cross-entity business rules
     */ 
}
