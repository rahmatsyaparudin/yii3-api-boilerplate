# Brand Module (API v1)

## Routes

Defined in `config/common/routes.php` under `/v1`:

- `GET /v1/brand` -> `BrandListAction`
- `POST /v1/brand/data` -> `BrandDataAction`
- `GET /v1/brand/{id}` -> `BrandViewAction`
- `POST /v1/brand` -> `BrandCreateAction`
- `PUT /v1/brand/{id}` -> `BrandUpdateAction`
- `DELETE /v1/brand/{id}` -> `BrandDeleteAction`

## Service and Repository

- `App\Domain\Brand\BrandService`
- `App\Domain\Brand\BrandRepositoryInterface`
- `App\Infrastructure\Persistence\Brand\DbBrandRepository`

## Filtering and sorting

Repository supports:

- filters: `id`, `name` (LIKE/ILIKE), `status`, `sync_mdb`
- sorting: `id`, `name`, `status`
