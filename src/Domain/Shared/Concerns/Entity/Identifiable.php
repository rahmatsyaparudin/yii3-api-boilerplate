<?php

declare(strict_types=1);

namespace App\Domain\Shared\Concerns\Entity;

use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;

trait Identifiable
{
    protected string $resource;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getResource(): string
    {
        return static::RESOURCE;
    }

    public function changeName(string $newName): void
    {
        $newName = trim($newName);
        if ($this->name === $newName) {
            return;
        }

        $this->validateName($newName);
        $this->name = $newName;
    }

    protected function validateName(string $name): void
    {
        if (empty($name)) {
            throw new BadRequestException(
                translate: new Message(
                    key: 'validation.name_required',
                    domain: 'validation',
                    params: ['resource' => $this->getResource()]
                )
            );
        }
    }
}