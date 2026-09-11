<?php

declare(strict_types=1);

namespace App\Domain\Shared\Core\Concerns\Service;

// Domain Layer
use App\Domain\Shared\Core\Security\AuthorizerInterface;
// Shared Layer
use App\Shared\Core\Exception\BadRequestException;
use App\Shared\Core\Exception\ConflictException;
use App\Shared\Core\Exception\ForbiddenException;
use App\Shared\Core\ValueObject\Message;

trait DomainValidator
{
    public function guardPermission(
        AuthorizerInterface $authorizer,
        string $permission,
        string $resource,
        ?int $id = null
    ): void {
        if (!$authorizer->can($permission)) {
            $action = \str_contains($permission, '.') ? \explode('.', $permission)[1] : $permission;

            throw new ForbiddenException(translate: Message::create(key: 'action_not_allowed', domain: 'validation', params: ['action' => $action, 'resource' => $resource, 'id' => $id]));
        }
    }

    public function ensureExists(?object $entity, string $resource): void
    {
        if (!$entity) {
            throw new BadRequestException(translate: Message::create(key: 'not_found', domain: 'validation', params: ['resource' => $resource]));
        }
    }

    public function ensureUnique(
        object $repository,
        string $value,
        string $field,
        string $resource,
        ?int $excludeId = null
    ): void {
        $methodName = 'findBy' . \ucfirst($field);

        if (!\method_exists($repository, $methodName)) {
            $exists = null;
        } else {
            $exists = $repository->$methodName($value);
        }

        if ($exists && ($excludeId === null || $exists->getId() !== $excludeId)) {
            throw new BadRequestException(translate: Message::create(key: 'exists.already_exists', domain: 'validation', params: ['resource' => $resource, 'field' => $field, 'value' => $value]));
        }
    }

    public function ensureIsAllowed(bool $canPerform, string $action, string $resource): void
    {
        if (!$canPerform) {
            throw new ForbiddenException(translate: Message::create(key: 'http.forbidden', params: ['action' => $action, 'resource' => $resource]));
        }
    }

    public function ensureDeletable(?object $entity, string $resource): void
    {
        $this->ensureExists($entity, $resource);

        if ($entity === null) {
            throw new BadRequestException(translate: Message::create(key: 'entity_required', domain: 'validation', params: ['resource' => $resource]));
        }

        $status = $entity->getStatus();

        if ($status->isLocked()) {
            throw new ConflictException(translate: Message::create(key: 'status.deletion_restricted', domain: 'validation', params: ['resource' => $resource, 'id' => $entity->getId(), 'status' => $status->label()]));
        }
    }
}
