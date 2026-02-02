<?php

declare(strict_types=1);

namespace App\Shared\Validation\Rules;

use Attribute;
use Yiisoft\Validator\RuleInterface;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class HasNoDependencies implements RuleInterface
{
    public function __construct(
        public array $map,
        public string $message = 'Data sedang digunakan di modul lain.',
    ) {}

    public function getName(): string { return 'hasNoDependencies'; }
    public function getHandler(): string { return HasNoDependenciesHandler::class; }
}