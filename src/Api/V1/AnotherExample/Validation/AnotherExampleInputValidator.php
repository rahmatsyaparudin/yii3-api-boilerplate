<?php

declare(strict_types=1);

namespace App\Api\V1\AnotherExample\Validation;

// Shared Layer
use App\Shared\Core\Context\ValidationContext;
use App\Shared\Core\Enums\RecordStatus;
use App\Shared\Core\Validation\AbstractValidator;
use App\Shared\Core\Validation\Rules\UniqueValue;
// Vendor Layer
use Yiisoft\Validator\Rule\In;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\StopOnError;
use Yiisoft\Validator\Rule\StringValue;

/**
 * AnotherExample Input Validator.
 */
final class AnotherExampleInputValidator extends AbstractValidator
{
    protected function rules(string $context): array
    {
        return match ($context) {
            ValidationContext::CREATE => [
                'name' => [
                    new StopOnError([
                        new Required(),
                        new StringValue(),
                        new Length(
                            min: 3,
                            max: 255,
                        ),
                        new UniqueValue(
                            table: 'another_example',
                            column: 'name',
                            ignoreId: null
                        ),
                    ]),
                ],
                'status' => [
                    new Required(),
                    new Integer(),
                    new In(
                        RecordStatus::draftOnlyStates(),
                    ),
                ],
                'example_id' => [
                    new Required(),
                    new Integer(
                        min: 1,
                    ),
                    new UniqueValue(
                        table: 'example',
                        column: 'id',
                        ignoreId: null
                    ),
                ],
            ],
            ValidationContext::UPDATE => [
                'id' => [
                    new Required(),
                    new Integer(
                        min: 1,
                    ),
                ],
                'name' => [
                    new StopOnError([
                        new StringValue(
                            skipOnEmpty: true,
                        ),
                        new Length(
                            min: 3,
                            max: 255,
                            skipOnEmpty: true,
                        ),
                        new UniqueValue(
                            table: 'another_example',
                            column: 'name',
                            ignoreId: $this->data['id'] ?? null
                        ),
                    ]),
                ],
                'status' => [
                    new Integer(
                        skipOnEmpty: true,
                    ),
                    new In(
                        RecordStatus::searchableStates(),
                    ),
                ],
                'example_id' => [
                    new Required(),
                    new Integer(
                        min: 1,
                    ),
                    new UniqueValue(
                        table: 'example',
                        column: 'id',
                        ignoreId: null
                    ),
                ],
                'lock_version' => [
                    new Required(
                        when: fn () => $this->shouldValidateOptimisticLock()
                    ),
                    new Integer(
                        min: 1,
                        skipOnEmpty: true,
                    ),
                ],
            ],
            ValidationContext::DELETE => [
                'id' => [
                    new Required(),
                    new Integer(
                        min: 1,
                    ),
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
                        min: 1,
                        max: 100,
                        skipOnEmpty: true,
                    ),
                ],
                'status' => [
                    new Integer(
                        skipOnEmpty: true,
                    ),
                ],
                'example_id' => [
                    new Integer(
                        min: 1,
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
                    new In(
                        ['asc', 'desc'],
                        skipOnEmpty: true,
                    ),
                ],
            ],

            default => [],
        };
    }
}
