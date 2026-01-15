<?php

declare(strict_types=1);

namespace App\Shared\Validation;

use App\Shared\Exception\ValidationException;
use App\Shared\Helper\ValidationHelper;
use App\Shared\Request\RawParams;
use Yiisoft\Validator\Validator;

abstract class AbstractValidator
{
    final public function validate(string $context, RawParams $data): void
    {
        // Convert RawParams to array for Yii3 validator
        $dataArray = $data->toArray();
        
        $result = $this->buildValidator()->validate($dataArray, $this->rules($context));

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
