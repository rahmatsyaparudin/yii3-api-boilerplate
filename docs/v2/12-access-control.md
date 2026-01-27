# Access Control

Access rules are defined in:

- `config/common/access.php`

Each key is a permission name (example `brand.view`) mapped to a callable that receives an `Actor` and returns boolean.

## Enforcement

Access rules are enforced automatically by `AccessMiddleware`, which runs before the Router. The middleware:

1. Matches the incoming request to a route using `UrlMatcher`.
2. Reads the `permission` metadata from the route’s defaults.
3. Retrieves the current `Actor` from `CurrentUser` (populated by `JwtMiddleware` from SSO JWT claims).
4. Evaluates the corresponding rule from the access map.
5. Throws `ForbiddenException` if the rule returns `false`.

## Configuration

- `app/config.code` (via `params.php`) determines the application code used for role lookups (e.g., `enterEDC`).
- Routes must include a `permission` key in their defaults to be protected.
- The `Actor` is populated from the SSO JWT payload’s `user` object, including nested roles.

## Example

```php
// config/common/access.php
$appCode = $params['app/config']['code'] ?? 'default';

$isKasir = static fn (Actor $actor): bool => $actor->hasRole($appCode, 'kasir');
$isAdmin = static fn (Actor $actor): bool => $actor->isAdmin($appCode);

return [
    'brand.data' => $isKasir,          // Requires 'kasir' role
    'brand.delete' => $isAdmin,        // Requires admin flag
];
```

```php
// config/common/routes.php
Route::post('/v1/brand/data')
    ->action(..., [BrandController::class, 'data'])
    ->defaults(['permission' => 'brand.data']);
```
