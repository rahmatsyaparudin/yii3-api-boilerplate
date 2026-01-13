<?php

declare(strict_types=1);

namespace App\Shared\Helper;

use Yiisoft\Validator\Result;

final class ValidationHelper
{
    /**
     * Ubah Yiisoft\Validator\Result jadi array siap JSON.
     */
    public static function formatErrors(Result $result): array
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
}
