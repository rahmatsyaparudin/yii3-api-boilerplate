<?php

declare(strict_types=1);

namespace App\Domain\Shared\Concerns\Service;

// Domain Layer
use App\Domain\Shared\Security\AuthorizerInterface;

// Shared Layer
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\ForbiddenException;
use App\Shared\ValueObject\Message;

trait DomainValidator
{
    public function guardPermission(
        AuthorizerInterface $authorizer,
        string $permission,
        string $resource,
        ?int $id = null
    ): void {
        if (!$authorizer->can($permission)) {
            $action = str_contains($permission, '.') ? explode('.', $permission)[1] : $permission;

            throw new ForbiddenException(
                translate: Message::create(
                    key: 'validation.action_not_allowed',
                    params: [
                        'action' => $action,
                        'resource' => $resource,
                        'id' => $id,
                    ]
                )
            );
        }
    }
    

    public function validateExists(?object $entity, string $resource): void
    {
        if (!$entity) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'validation.not_found',
                    domain: 'validation',
                    params: [
                        'resource' => $resource,
                    ]
                )
            );
        }
    }

    public function validateUniqueValue(
        object $repository,
        string $value,
        string $field,
        string $resource,
        ?int $excludeId = null
    ): void {
        $methodName = 'findBy' . ucfirst($field);
        
        if (!method_exists($repository, $methodName)) {
            $exists = null;
        } else {
            $exists = $repository->$methodName($value);
        }

        if ($exists && ($excludeId === null || $exists->getId() !== $excludeId)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'exists.already_exists',
                    domain: 'validation',
                    params: [
                        'resource' => $resource,
                        'field' => $field,
                        'value' => $value
                    ]
                )
            );
        }
    }

    public function validateCanPerformAction(bool $canPerform, string $action, string $resource): void
    {
        if (!$canPerform) {
            throw new ForbiddenException(
                translate: Message::create(
                    key: 'http.forbidden',
                    params: [
                        'action' => $action,
                        'resource' => $resource
                    ]
                )
            );
        }
    }

    public function validateCanBeDeleted(?object $entity, string $resource): void
    {
        $this->validateExists($entity, $resource);
        
        if ($entity === null) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'validation.entity_required',
                    domain: 'validation',
                    params: ['resource' => $resource]
                )
            );
        }
        
        $status = $entity->getStatus();

        if ($status->isLocked()) {
            throw new ConflictException(
                translate: Message::create(
                    key: 'status.deletion_restricted',
                    domain: 'validation',
                    params: [
                        'resource' => $resource,
                        'id' => $entity->getId(),
                        'status' => $status->label()
                    ]
                )
            );
        }
    }
}