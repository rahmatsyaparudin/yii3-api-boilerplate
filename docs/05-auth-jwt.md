# Authentication (JWT)

## Components

- Middleware: `src/Shared/Middleware/JwtMiddleware.php`
- Service: `src/Infrastructure/Security/JwtService.php`
- Actor provider: `src/Infrastructure/Security/ActorProvider.php`
- Current user: `src/Infrastructure/Security/CurrentUser.php`

## Flow

- Extract `Authorization: Bearer <token>`
- Decode token using `JwtService`
- Convert claims to `App\Domain\Common\Audit\Actor`
- Store actor in `CurrentUser`

On failures, `UnauthorizedException` is thrown.
