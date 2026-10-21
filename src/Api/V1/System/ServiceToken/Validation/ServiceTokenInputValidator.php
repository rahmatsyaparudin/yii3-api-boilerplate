<?php

declare(strict_types=1);

namespace App\Api\V1\System\ServiceToken\Validation;

// Shared Layer
use App\Shared\Common\Context\ValidationContext;
use App\Shared\Core\Validation\AbstractValidator;
// Vendor Layer
use Yiisoft\Validator\Rule\In;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Regex;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\StopOnError;
use Yiisoft\Validator\Rule\StringValue;

/**
 * Service Token Input Validator.
 *
 * Context-based validation rules for service token operations
 * (create, update, delete, search) via AbstractValidator.
 */
final class ServiceTokenInputValidator extends AbstractValidator
{
    private const EXPIRES_AT_PATTERN = '/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}(:\d{2})?)?$/';

    protected function rules(string $context): array
    {
        return match ($context) {
            ValidationContext::CREATE => [
                'username' => [
                    new StopOnError([
                        new Required(),
                        new StringValue(),
                        new Length(
                            max: 100,
                        ),
                    ]),
                ],
                'name' => [
                    new StopOnError([
                        new Required(),
                        new StringValue(),
                        new Length(
                            max: 100,
                        ),
                    ]),
                ],
                'expires_at' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                    new Regex(
                        pattern: self::EXPIRES_AT_PATTERN,
                        skipOnEmpty: true,
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
                'username' => [
                    new StopOnError([
                        new StringValue(
                            skipOnEmpty: true,
                        ),
                        new Length(
                            max: 100,
                            skipOnEmpty: true,
                        ),
                    ]),
                ],
                'name' => [
                    new StopOnError([
                        new StringValue(
                            skipOnEmpty: true,
                        ),
                        new Length(
                            max: 100,
                            skipOnEmpty: true,
                        ),
                    ]),
                ],
                'expires_at' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                    new Regex(
                        pattern: self::EXPIRES_AT_PATTERN,
                        skipOnEmpty: true,
                    ),
                ],
                'regenerate' => [
                    new In(
                        [true, false, 0, 1, '0', '1'],
                        skipOnEmpty: true,
                    ),
                ],
                'status' => [
                    new Integer(
                        skipOnEmpty: true,
                    ),
                    new In(
                        [0, 1, 4],
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
                'user_id' => [
                    new Integer(
                        skipOnEmpty: true,
                    ),
                ],
                'username' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                ],
                'name' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                ],
                'status' => [
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
