<?php

declare(strict_types=1);

namespace App\Domain\Brand;

use App\Shared\Validation\AbstractValidator;
use App\Shared\Validation\ValidationContext;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\StringValue;

final class BrandValidator extends AbstractValidator
{
    protected function rules(string $context): array
    {
        return match ($context) {
            ValidationContext::SEARCH => [
                'id' => new Integer(
                    skipOnEmpty: true,
                ),
                'name' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                    new Length(
                        max: 3,
                        skipOnEmpty: true,
                    ),
                ],
                'status' => new Integer(
                    skipOnEmpty: true,
                ),
                'sync_mdb' => new Integer(
                    skipOnEmpty: true,
                ),
                'page' => new Integer(
                    min: 1,
                    skipOnEmpty: true,
                ),
                'page_size' => new Integer(
                    min: 1,
                    max: 200,
                    skipOnEmpty: true,
                ),
                'sort_by' => new StringValue(
                    skipOnEmpty: true,
                ),
                'sort_dir' => new StringValue(
                    skipOnEmpty: true,
                ),
            ],

            ValidationContext::CREATE => [
                'name' => [new Required(), new StringValue(), new Length(max: 100)],
                // 'status' => new Integer(),
            ],

            ValidationContext::UPDATE => [
                'name'   => [new StringValue(), new Length(max: 100)],
                'status' => new Integer(),
            ],

            default => [],
        };
    }
}
