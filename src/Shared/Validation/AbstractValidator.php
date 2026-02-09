<?php

declare(strict_types=1);

namespace App\Shared\Validation;

// Vendor Layer
use Yiisoft\Validator\Result;
use Yiisoft\Validator\ValidatorInterface;

// Shared Layer
use App\Shared\Exception\ValidationException;
use App\Shared\Request\RawParams;

use App\Shared\ValueObject\LockVersionConfig;

abstract class AbstractValidator
{
    protected array $data = [];
    protected mixed $id = null;

    public function __construct(
        protected LockVersionConfig $lockVersionConfig,
        protected ValidatorInterface $validator
    ) {}
    
    final public function validate(string $context, RawParams $data): void
    {
        $this->data = $data->toArray();

        $this->id = $this->data['id'] ?? null;
        
        $result = $this->validator->validate($this->data, $this->rules($context));

        if (!$result->isValid()) {
            throw new ValidationException(
                $this->formatErrors($result)
            );
        }
    }
    
    private function formatErrors(Result $result): array
    {
        $errors = [];

        foreach ($result->getErrors() as $error) {
            $path = $error->getValuePath();
            $property = implode('.', $path);
            
            $message = $error->getMessage();
            $parameters = $error->getParameters();

            foreach ($parameters as $key => $value) {
                if (is_scalar($value)) {
                    $message = str_replace('{' . $key . ', number}', (string)$value, $message);
                    $message = str_replace('{' . $key . '}', (string)$value, $message);
                }
            }

            if (str_contains($message, 'plural')) {
                $message = preg_replace('/\{(\w+), plural, .*?other\{(.*?)\}\}/', '$2', $message);
            }

            $message = str_replace('{Property}', $property, $message);

            $errors[] = [
                'field'   => $property,
                'message' => trim($message),
            ];
        }

        return $errors;
    }

    protected function isOptimisticLockEnabled(): bool
    {
        $shortName = (new \ReflectionClass($this))->getShortName();
        return $this->lockVersionConfig->isEnabledFor($shortName);
    }
    
    protected function shouldValidateOptimisticLock(): bool
    {
        $shortName = (new \ReflectionClass($this))->getShortName();
        return $this->lockVersionConfig->isEnabledFor($shortName);
    }
    
    abstract protected function rules(string $context): array;
}
