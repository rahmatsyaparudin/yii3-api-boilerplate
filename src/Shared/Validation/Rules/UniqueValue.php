<?php

declare(strict_types=1);

namespace App\Shared\Validation\Rules;

use Yiisoft\Validator\RuleInterface;

final class UniqueValue implements RuleInterface
{
    public function __construct(
        public string $table,
        public string $column,
        public mixed $ignoreId = null,
        public string $idColumn = 'id',
        public string $message = 'Data ini sudah ada di database.',
    ) {}

    public function getName(): string
    {
        return 'uniqueValue';
    }

    public function getHandler(): string
    {
        return UniqueValueHandler::class;
    }
}