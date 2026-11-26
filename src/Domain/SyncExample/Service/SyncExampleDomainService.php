<?php

declare(strict_types=1);

namespace App\Domain\SyncExample\Service;

// Domain Layer
use App\Domain\Shared\Core\Concerns\Service\DomainValidator;
use App\Domain\SyncExample\Repository\SyncExampleRepositoryInterface;

/**
 * SyncExample Domain Service.
 *
 * Pure business logic and domain rules.
 */
final class SyncExampleDomainService
{
    use DomainValidator;

    public function __construct(
        private SyncExampleRepositoryInterface $repository
    ) {
    }

    /*
     * Place SyncExample-specific business logic here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     *
     * SyncExamples:
     * - Complex validation rules
     * - Business calculations
     * - Domain-specific operations
     * - Cross-entity business rules
     */
}
