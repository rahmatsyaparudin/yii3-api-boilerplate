# Shared Exceptions

Exception classes yang dapat digunakan di seluruh aplikasi untuk handling HTTP errors dengan konsisten.

## Base Exception

### HttpException (Abstract)
Base class untuk semua HTTP exceptions. Menyimpan HTTP status code.

```php
abstract class HttpException extends RuntimeException
{
    public function getHttpStatusCode(): int;
}
```

## Available Exceptions

### UnauthorizedException (401)
Untuk authentication errors.

```php
throw new UnauthorizedException('Invalid JWT token');
```

**Use cases:**
- JWT token invalid/expired
- Missing authentication credentials
- Invalid API key

### ForbiddenException (403)
Untuk authorization errors (user authenticated tapi tidak punya akses).

```php
throw new ForbiddenException('You do not have permission to access this resource');
```

**Use cases:**
- User tidak punya role yang diperlukan
- Access denied to specific resource
- Insufficient permissions

### NotFoundException (404)
Untuk resource yang tidak ditemukan.

```php
throw new NotFoundException('Brand not found');
```

**Use cases:**
- Entity tidak ditemukan di database
- Route tidak ada
- File tidak ditemukan

### BadRequestException (400)
Untuk request yang invalid.

```php
throw new BadRequestException('Invalid request format');
```

**Use cases:**
- Malformed JSON
- Missing required parameters
- Invalid data format

### ValidationException (422)
Untuk validation errors.

```php
throw new ValidationException('Validation failed');
```

**Use cases:**
- Form validation errors
- Business rule violations
- Data constraint violations

### ConflictException (409)
Untuk conflict dengan state yang ada.

```php
throw new ConflictException('Brand with this name already exists');
```

**Use cases:**
- Duplicate entry
- Concurrent modification
- State conflict

## Exception Handler

Semua `HttpException` akan di-handle oleh `ExceptionResponderFactory` dan dikonversi ke JSON response:

```json
{
  "status": 401,
  "success": false,
  "message": "Invalid JWT token",
  "payload": null
}
```

## Usage Example

```php
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\UnauthorizedException;

class BrandService
{
    public function findById(int $id): Brand
    {
        $brand = $this->repository->findById($id);
        
        if ($brand === null) {
            throw new NotFoundException("Brand with ID {$id} not found");
        }
        
        return $brand;
    }
}

class JwtMiddleware
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$authHeader) {
            throw new UnauthorizedException('Authorization header missing');
        }
        
        // ...
    }
}
```

## Creating Custom Exceptions

Untuk membuat exception baru, extend dari `HttpException`:

```php
<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Yiisoft\Http\Status;

final class TooManyRequestsException extends HttpException
{
    public function __construct(string $message = 'Too many requests', ?\Throwable $previous = null)
    {
        parent::__construct($message, Status::TOO_MANY_REQUESTS, $previous);
    }
}
```
