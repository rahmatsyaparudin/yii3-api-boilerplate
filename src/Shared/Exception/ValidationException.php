<?php
declare(strict_types=1);

namespace App\Shared\Exception;

use Yiisoft\Http\Status;

final class ValidationException extends HttpException
{
    public function __construct(
        string $message = 'Validation failed',
        private readonly array $errors = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, Status::UNPROCESSABLE_ENTITY, $errors, $previous);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
