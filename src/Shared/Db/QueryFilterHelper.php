<?php

declare(strict_types=1);

namespace App\Shared\Db;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

final class QueryFilterHelper
{
    public static function andFilterWhere(Query $query, array $conditions): Query
    {
        foreach ($conditions as $key => $value) {
            if ($value !== null && $value !== '') {
                $query->andWhere([$key => $value]);
            }
        }

        return $query;
    }

    public static function orFilterWhere(Query $query, array $conditions): Query
    {
        $or = [];
        foreach ($conditions as $key => $value) {
            if ($value !== null && $value !== '') {
                $or[] = [$key => $value];
            }
        }
        if ($or) {
            $query->orWhere($or);
        }

        return $query;
    }

    public static function andFilterLike(ConnectionInterface $db, Query $query, array $conditions): Query
    {
        $operator = self::likeOperator($db);
        foreach ($conditions as $key => $value) {
            if ($value !== null && $value !== '') {
                $query->andWhere([$operator, $key, $value]);
            }
        }

        return $query;
    }

    public static function orFilterLike(ConnectionInterface $db, Query $query, array $conditions): Query
    {
        $likes    = [];
        $operator = self::likeOperator($db);
        foreach ($conditions as $key => $value) {
            if ($value !== null && $value !== '') {
                $likes[] = [$operator, $key, $value];
            }
        }
        if ($likes) {
            $query->orWhere($likes);
        }

        return $query;
    }

    private static function likeOperator(ConnectionInterface $db): string
    {
        $driverName = \strtolower($db->getDriverName());

        return \str_contains($driverName, 'pgsql') || \str_contains($driverName, 'postgres')
            ? 'ilike'
            : 'like';
    }

    public static function andFilterIn(Query $query, array $conditions): Query
    {
        foreach ($conditions as $key => $values) {
            if (!empty($values) && \is_array($values)) {
                $query->andWhere([$key => $values]);
            }
        }

        return $query;
    }

    public static function orFilterIn(Query $query, array $conditions): Query
    {
        $or = [];
        foreach ($conditions as $key => $values) {
            if (!empty($values) && \is_array($values)) {
                $or[] = [$key => $values];
            }
        }
        if ($or) {
            $query->orWhere($or);
        }

        return $query;
    }

    public static function andFilterRange(Query $query, array $ranges): Query
    {
        foreach ($ranges as $key => $range) {
            if (!\is_array($range)) {
                continue;
            }
            if (isset($range['min'])) {
                $query->andWhere(['>=', $key, $range['min']]);
            }
            if (isset($range['max'])) {
                $query->andWhere(['<=', $key, $range['max']]);
            }
        }

        return $query;
    }
}
