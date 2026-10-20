# Panduan Shared Components

## 📋 Gambaran Umum

Panduan ini mencakup shared components di `src/Shared/` (ditambah kode shared kernel di `src/Domain/Shared/` dan `src/Infrastructure/Core/`). Komponen-komponen ini menyediakan fungsionalitas yang dapat digunakan kembali di seluruh layer aplikasi, mengikuti prinsip Domain-Driven Design (DDD) dan mendorong reusabilitas kode.

---

## 🏗️ Gambaran Umum Arsitektur Shared

### Struktur Direktori

```
src/Shared/
├── ApplicationParams.php   # readonly app name/version/language/environment DTO
├── Common/
│   └── Context/
│       └── ValidationContext.php   # implements ValidationContextInterface
└── Core/
    ├── Dto/                # SearchCriteria, PaginatedResult
    ├── Enums/              # AppConstants, RecordStatus
    ├── ErrorHandler/       # ErrorHandlerResponse (ThrowableRendererInterface)
    ├── Exception/          # HttpException + final concrete exceptions
    ├── Middleware/         # PSR-15 middleware (JWT, CORS, rate limit, ...)
    ├── Query/              # QueryConditionApplier (static helpers)
    ├── Request/            # RawParams, RequestParams, PaginationParams, SortParams, ...
    ├── Security/           # InputSanitizer (static)
    ├── Utility/            # Arrays, FieldMapper, JsonHandler
    ├── Validation/         # AbstractValidator, ValidationContextInterface, Rules/
    └── ValueObject/        # Message, LockVersionConfig

src/Domain/Shared/Core/
├── Audit/                  # AuditServiceInterface
├── Concerns/
│   ├── Entity/             # Identifiable, Stateful, Descriptive, ChangeLogged
│   └── Service/            # DomainValidator (guard* / ensure* helpers)
├── Contract/               # ActorInterface, CurrentUserInterface, DateTimeProviderInterface
├── Enum/                   # SyncStatus, SyncDirection
├── Security/               # AuthorizerInterface
└── ValueObject/            # DetailInfo, LockVersion, ResourceStatus, SyncFlag, SyncMdb

src/Infrastructure/Core/
├── Concerns/               # HasCoreFeatures, HasMongoDBSync, ManagesPersistence, Auditable
└── Security/               # Actor, CurrentUser, JwtService, AccessChecker, ...
```

### Kategori Komponen

#### **1. Data Management**
- **Dto/**: `SearchCriteria`, `PaginatedResult` untuk endpoint list
- **ValueObject/**: Value object immutable (`Message`, `LockVersion`, `ResourceStatus`, `DetailInfo`, `SyncFlag`, `SyncMdb`, `LockVersionConfig`)
- **Query/**: Helper kondisi static `QueryConditionApplier`

#### **2. Request & Response**
- **Request/**: `RawParams`, `RequestParams`, `PaginationParams`, `SortParams`, `RequestDataParser`
- **Middleware/**: Middleware PSR-15 untuk request pipeline

#### **3. Error Handling**
- **Exception/**: Hierarki `HttpException`
- **ErrorHandler/**: Renderer JSON `ErrorHandlerResponse`

#### **4. Business Logic**
- **Enums/**: Enum `RecordStatus`, konstanta `AppConstants`
- **Domain Enum/**: `SyncStatus`, `SyncDirection`
- **Validation/**: `AbstractValidator`, `ValidationContextInterface`, rule kustom (`UniqueValue`, `HasNoDependencies`)

#### **5. Infrastructure**
- **Security/**: `InputSanitizer` (pembersihan input)
- **Utility/**: `Arrays`, `FieldMapper`, `JsonHandler`
- **Concerns/**: Trait entity/repository (`HasCoreFeatures`, `ManagesPersistence`, `HasMongoDBSync`, `Auditable`)

---

## 📚 Panduan Komponen Individual

### 1. [Data Transfer Objects (Dto)](09-dto-guide.md)
Tujuan: Struktur data untuk komunikasi API dan transfer data antar layer.

### 2. [Enumerations (Enums)](11-enums-guide.md)
Tujuan: Enumerasi dan konstanta bersama yang digunakan di seluruh aplikasi.

### 3. [Error Handling](13-error-handling-guide.md)
Tujuan: Utility error handling dan manajemen exception.

### 4. [Exceptions](14-exceptions-guide.md)
Tujuan: Class exception kustom untuk berbagai skenario error.

### 5. [Middleware](16-middleware-guide.md)
Tujuan: Komponen middleware HTTP untuk pipeline pemrosesan request.

### 6. [Query Building](10-query-guide.md)
Tujuan: Utility query building untuk operasi database.

### 7. [Request Processing](06-request-guide.md)
Tujuan: Pemrosesan request HTTP dan utility validasi.

### 8. [Security](15-security-guide.md)
Tujuan: Utility keamanan dan helper autentikasi.

### 9. [Utilities](20-utility-guide.md)
Tujuan: Fungsi utility umum dan class helper.

### 10. [Validation](07-validation-guide.md)
Tujuan: Rule validasi dan utility validasi data.

### 11. [Value Objects](12-value-object-guide.md)
Tujuan: Value object immutable dengan validasi bawaan.

### 12. [Application Parameters](04-application-params-guide.md)
Tujuan: Manajemen dan konfigurasi parameter aplikasi.

---

## 🔧 Pola Penggunaan

### 1. **Dependency Injection**
```php
// Shared components are designed for DI injection
final class ExampleApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private ExampleRepositoryInterface $repository,
    ) {}
}
```

### 2. **Static Utilities**
```php
// Some utilities provide static methods
use App\Shared\Core\Query\QueryConditionApplier;
use App\Shared\Core\Security\InputSanitizer;
use App\Shared\Core\Utility\Arrays;

$clean    = InputSanitizer::process($input);
$filtered = Arrays::removeNulls($data);
QueryConditionApplier::filterByExactMatch($query, $filters, $allowedColumns);
```

### 3. **Value Objects**
```php
// Immutable value objects / enums
use App\Domain\Shared\Core\ValueObject\ResourceStatus;
use App\Shared\Core\Enums\RecordStatus;
use App\Shared\Core\ValueObject\Message;

$status  = ResourceStatus::active();           // VO wrapping RecordStatus::ACTIVE
$message = Message::create(key: 'resource.not_found', params: ['resource' => 'Example']);
$state   = RecordStatus::ACTIVE;               // backed enum case
```

### 4. **Exception Handling**
```php
// Custom exceptions carry a Message value object
use App\Shared\Core\Exception\NotFoundException;

throw new NotFoundException(
    translate: Message::create(
        key: 'resource.not_found',
        params: ['resource' => 'User', 'field' => 'id', 'value' => $id],
    ),
);
```

---

## 🎯 Prinsip Desain

### 1. **Single Responsibility**
- Setiap komponen memiliki satu tujuan yang jelas dan terdefinisi
- Komponen fokus dan mudah dipelihara

### 2. **Reusability**
- Komponen dirancang untuk digunakan kembali di berbagai layer
- Tidak ada coupling ke logika domain tertentu

### 3. **Type Safety**
- Pengetikan kuat dengan fitur PHP 8+
- Value object memastikan integritas data

### 4. **Immutability**
- Value object dan DTO bersifat `readonly`
- Mencegah perubahan state yang tidak disengaja

### 5. **Testability**
- Komponen mudah diuji
- Dependency injection memungkinkan mocking

---

## 📊 Contoh Integrasi

### 1. **Integrasi Action**
```php
final class ExampleDataAction
{
    public function __construct(
        private SearchCriteriaFactory $factory,
        private ExampleApplicationService $applicationService,
        private ResponseFactory $responseFactory,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        /** @var RequestParams $payload — set by RequestParamsMiddleware */
        $payload = $request->getAttribute('payload');

        $criteria = $this->factory->createFromRequest(
            params: $payload,
            allowedSort: ['id' => 'id', 'name' => 'name'],
        );

        $result = $this->applicationService->list(criteria: $criteria);

        return $this->responseFactory->success(
            data: $result->data,
            meta: $result->getMeta(),
        );
    }
}
```

### 2. **Integrasi Service**
```php
final class ExampleApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private ExampleRepositoryInterface $repository,
        private ExampleDomainService $domainService,
    ) {}

    public function delete(int $id): ExampleResponse
    {
        $this->domainService->guardPermission(
            id: $id,
            authorizer: $this->auth,
            permission: 'example.delete',
            resource: $this->getResource(),
        );
        // ...
    }
}
```

### 3. **Integrasi Repository**
```php
final class ExampleRepository implements ExampleRepositoryInterface, CurrentUserAwareInterface
{
    use HasCoreFeatures;
    use HasMongoDBSync;
    use ManagesPersistence;

    public function findById(int $id, ?int $status = null): ?Example
    {
        $row = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where(['id' => $id])
            ->andWhere($this->scopeWhereNotDeleted())
            ->andWhere($this->scopeByStatus($status))
            ->one();

        return $row ? Example::reconstitute(/* ... */) : null;
    }
}
```

---

## 🚀 Praktik Terbaik

### 1. **Penggunaan Komponen**
```php
// ✅ Use dependency injection
public function __construct(
    private ExampleRepositoryInterface $repository
) {}

// ❌ Avoid manual instantiation of services
$repository = new ExampleRepository($db);
```

### 2. **Error Handling**
```php
// ✅ Use custom exceptions
throw new NotFoundException(translate: Message::create(key: 'resource.not_found', params: [...]));

// ❌ Avoid generic exceptions
throw new RuntimeException('User not found');
```

### 3. **Validation**
```php
// ✅ Use value objects and validators
$status = ResourceStatus::from($input);       // throws on invalid value
$validator->validate($data, ValidationContextInterface::CREATE);

// ❌ Avoid scattered manual checks
if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
    throw new ValidationException();
}
```

### 4. **Data Transfer**
```php
// ✅ Use typed DTOs / commands for API communication
final readonly class CreateExampleCommand
{
    public function __construct(
        public string $name,
        public int $status,
    ) {}
}

// ❌ Avoid passing raw associative arrays between layers
$data = ['name' => $name, 'status' => $status];
```

---

## 📚 Dokumentasi Terkait

- **[Architecture Guide](02-architecture-guide.md)**: Gambaran umum arsitektur lengkap
- **[DI Configuration Guide](05-di-configuration-guide.md)**: Setup dependency injection
- **[Migration & Seeding Guide](17-migration-seeding-guide.md)**: Migrasi database dan seeder
- **[Quality Guide](21-quality-guide.md)**: Tooling dan standar kualitas kode
- **[Setup Guide](01-setup-guide.md)**: Setup proyek dan konfigurasi environment

---

## 🎯 Ringkasan

Shared components menyediakan fondasi yang kuat untuk membangun kode yang mudah dipelihara, dapat diuji, dan dapat digunakan kembali di aplikasi Yii3 API. Manfaat utamanya meliputi:

- **🔄 Reusability**: Komponen dapat digunakan di berbagai layer
- **🛡️ Type Safety**: Pengetikan kuat mencegah runtime error
- **🧪 Testability**: Mudah di-unit test dengan dependency injection
- **📦 Modularity**: Setiap komponen memiliki single responsibility
- **🔧 Maintainability**: Separation of concerns yang jelas
- **🚀 Performance**: Dioptimalkan untuk kasus penggunaan umum

Dengan mengikuti pola dan praktik terbaik yang diuraikan dalam panduan ini, Anda dapat membangun shared components yang robust dan mudah dipelihara untuk aplikasi Yii3 API Anda! 🚀
