<?php

declare(strict_types=1);

namespace App\Shared\Validation;

// Vendor Layer
use Yiisoft\Validator\Result;
use Yiisoft\Validator\Validator;

// Shared Layer
use App\Shared\Exception\ValidationException;
use App\Shared\Request\RawParams;

use App\Shared\ValueObject\LockVersionConfig;

abstract class AbstractValidator
{
    public function __construct(
        protected LockVersionConfig $lockVersionConfig
    ) {}
    
    final public function validate(string $context, RawParams $data): void
    {
        // Convert RawParams to array for Yii3 validator
        $dataArray = $data->toArray();
        
        $result = $this->buildValidator()->validate($dataArray, $this->rules($context));

        if (!$result->isValid()) {
            throw new ValidationException(
                $this->formatErrors($result)
            );
        }
    }

    protected function buildValidator(): Validator
    {
        return new Validator();
    }
    
    private function formatErrors(Result $result): array
    {
        $errors = [];
        foreach ($result->getErrorMessagesIndexedByPath() as $property => $errorList) {
            foreach ($errorList as $message) {
                $errors[] = [
                    'field'   => $property,
                    'message' => $message,
                ];
            }
        }
        return $errors;
    }

    protected function isOptimisticLockEnabled(): bool
    {
        $shortName = (new \ReflectionClass($this))->getShortName();
        return $this->lockVersionConfig->isEnabledFor($shortName);
    }
    
    abstract protected function rules(string $context): array;
}
