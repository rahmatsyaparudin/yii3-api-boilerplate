<?php

declare(strict_types=1);

namespace App\Shared\Validation;

use App\Shared\Exception\ValidationException;
use App\Shared\Helper\ValidationHelper;
use Yiisoft\Validator\Validator;

abstract class AbstractValidator
{
    final public function validate(string $context, array $data): void
    {
        $result = $this->buildValidator()->validate($data, $this->rules($context));

        if (!$result->isValid()) {
            throw new ValidationException(
                ValidationHelper::formatErrors($result)
            );
        }
    }

    protected function buildValidator(): Validator
    {
        return new Validator();
    }

    abstract protected function rules(string $context): array;
}
