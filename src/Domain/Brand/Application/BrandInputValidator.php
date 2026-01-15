<?php

declare(strict_types=1);

namespace App\Domain\Brand\Application;

use App\Shared\Validation\AbstractValidator;
use App\Shared\Validation\ValidationContext;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\StringValue;

/**
 * Application Layer Validation
 * 
 * Validasi untuk use case specific (CREATE, UPDATE, SEARCH)
 * Ini adalah input validation, bukan business rules
 */
final class BrandInputValidator extends AbstractValidator
{
    protected function rules(string $context): array
    {
        return match ($context) {
            ValidationContext::CREATE => [
                'name' => [
                    new Required(),
                    new StringValue(),
                    new Length(max: 255),
                ],
            ],
            ValidationContext::UPDATE => [
                'name'   => [
                    new StringValue(
                        skipOnEmpty: true,
                    ), 
                    new Length(
                        max: 100,
                        skipOnEmpty: true,
                    )
                ],
                'status' => [
                    new Integer(
                        skipOnEmpty: true,
                    ),
                ],
            ],
             ValidationContext::DELETE => [
                'id'   => [
                    new Required(), 
                    new Integer(), 
                ],
            ],
            ValidationContext::SEARCH => [
                'id' => [
                    new Integer(
                        skipOnEmpty: true,
                    ),
                ],
                'name' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                    new Length(
                        max: 100,
                        skipOnEmpty: true,
                    ),
                ],
                'status' => [
                    new Integer(
                        skipOnEmpty: true,
                    ),
                ],
                'sync_mdb' => [
                    new Integer(
                        skipOnEmpty: true,
                    ),
                ],
                'page' => [
                    new Integer(
                        min: 1,
                        skipOnEmpty: true,
                    ),
                ],
                'page_size' => [
                    new Integer(
                        min: 1,
                        max: 200,
                        skipOnEmpty: true,
                    ),
                ],
                'sort_by' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                ],
                'sort_dir' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                ],
            ],

            default => [],
        };
    }
}