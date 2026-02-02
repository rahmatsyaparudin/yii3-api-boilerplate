<?php

declare(strict_types=1);

namespace App\Shared\Validation\Rules;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\RuleHandlerInterface;
use Yiisoft\Validator\ValidationContext;

final class HasNoDependenciesHandler implements RuleHandlerInterface
{
    public function __construct(private ConnectionInterface $db) {}

    public function validate(mixed $value, object $rule, ValidationContext $context): Result
    {
        $result = new Result();
        foreach ($rule->map as $table => $columns) {
            $exists = (new Query())->from($table)
                ->where(['or', ...array_map(fn($col) => [$col => $value], (array)$columns)])
                ->exists($this->db);
            
            if ($exists) {
                $result->addError($rule->message);
                break;
            }
        }
        return $result;
    }
}