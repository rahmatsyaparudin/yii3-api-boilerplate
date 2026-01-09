# Error Handling and i18n

## Exception to response

`App\Api\Shared\ExceptionResponderFactory` converts exceptions into API responses.

- `App\Shared\Exception\HttpException` and its subclasses are returned as `fail()`.
- Messages are translated using `TranslatorInterface` category `error`.

## Translation files

- `resources/messages/en/error.php`
- `resources/messages/id/error.php`

## Custom HTTP exceptions

Located in `src/Shared/Exception/*`.

- `BadRequestException` (400)
- `UnauthorizedException` (401)
- `ForbiddenException` (403)
- `NotFoundException` (404)
- `ConflictException` (409)
- `ValidationException` (422)
