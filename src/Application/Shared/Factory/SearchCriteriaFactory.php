<?php

declare(strict_types=1);

namespace App\Application\Shared\Factory;

use App\Shared\Dto\SearchCriteria;
use App\Shared\Request\RequestParams;

final class SearchCriteriaFactory
{
    public function createFromRequest(
        RequestParams $params, 
        array $allowedSort, 
        int $defaultPageSize = 15
    ): SearchCriteria {
        $pagination = $params->getPagination();
        $sort = $params->getSort();

        return new SearchCriteria(
            filter: $params->getFilter()->toArray(),
            page: $pagination->page ?? 1,
            pageSize: $pagination->page_size ?? $defaultPageSize,
            sortBy: $sort->by ?? array_key_first($allowedSort),
            sortDir: $sort->dir ?? 'asc',
            allowedSort: $allowedSort
        );
    }
}