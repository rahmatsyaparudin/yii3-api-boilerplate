<?php

declare(strict_types=1);

namespace App\Shared\Validation\Rules;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Translator\TranslatorInterface; // Import ini
use Yiisoft\Validator\Result;
use Yiisoft\Validator\RuleHandlerInterface;
use Yiisoft\Validator\ValidationContext;

final class UniqueValueHandler implements RuleHandlerInterface
{
    public function __construct(
        private ConnectionInterface $db,
        private TranslatorInterface $translator // Suntikkan Translator
    ) {}

    public function validate(mixed $value, object $rule, ValidationContext $context): Result
    {
        $query = (new Query($this->db))
            ->from($rule->table)
            ->where([$rule->column => $value]);

        if ($rule->ignoreId !== null) {
            $query->andWhere(['!=', $rule->idColumn, $rule->ignoreId]);
        }

        $result = new Result();

        if ($query->exists()) {
            // Gunakan translator secara manual untuk menerjemahkan key
            $translatedMessage = $this->translator->translate(
                id: 'exists.already_exists',
                parameters: [
                    'resource' => $rule->table,
                    'field' => $rule->column,
                    'value' => (string)$value
                ],
                category: 'validation' // Sesuaikan dengan kategori di translator Anda
            );

            $result->addError($translatedMessage);
        }

        return $result;
    }
}