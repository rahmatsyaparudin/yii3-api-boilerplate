# Request Params, Pagination, Sorting, Filtering

## RequestParams

`App\Shared\Request\RequestParams` extracts:

- `page`
- `page_size`
- `sort_by`
- `sort_dir`
- `filters` (all other keys)

## Middleware

`App\Shared\Middleware\RequestParamsMiddleware` attaches `RequestParams` to request attributes.

## Filtering

Whitelist filtering is applied at action level using `App\Shared\Helper\FilterHelper::onlyAllowed()`.
