# 📁 Struktur Lengkap Folder & File `src/`

## 🏗️ **Arsitektur Overview**

```
src/
├── 📂 Api/                    (19 files) - API Layer
├── 📂 Application/             (5 files)  - Application Service Layer  
├── 📂 Console/                 (1 file)   - Console Commands
├── 📂 Domain/                 (14 files) - Domain Layer
├── 📂 Infrastructure/          (23 files) - Infrastructure Layer
├── 📂 Migration/               (3 files)  - Database Migrations
├── 📂 Shared/                  (37 files) - Shared Utilities
├── 📄 Environment.php          (1 file)   - Environment Config
└── 📄 autoload.php             (1 file)   - Autoloader
```

**📊 Statistik:**
- 🎯 **Total Files: 56 PHP files** (reduced from 57)
- 🌐 **API Layer:** 19 files (34%)
- ⚙️ **Application Layer:** 5 files (9%)
- 🏛️ **Domain Layer:** 14 files (25%)
- 🔧 **Infrastructure Layer:** 23 files (41%)
- 🗄️ **Migration Layer:** 3 files (5%)
- 🛠️ **Shared Layer:** 37 files (66%) - reduced from 38
- 💻 **Console Layer:** 1 file (2%)

---

## 🌐 **1. API Layer (19 files)**

### 📂 Struktur Folder:
```
Api/
├── 📄 IndexAction.php
├── 📂 Shared/ (11 files)
│   ├── 📄 ExceptionResponderFactory.php
│   ├── 📄 NotFoundMiddleware.php
│   ├── 📄 ResponseFactory.php
│   └── 📂 Presenter/ (9 files)
│       ├── 📄 AsIsPresenter.php
│       ├── 📄 CollectionPresenter.php
│       ├── 📄 FailPresenter.php
│       ├── 📄 OffsetPaginatorPresenter.php
│       ├── 📄 PresenterInterface.php
│       ├── 📄 SuccessPresenter.php
│       ├── 📄 SuccessWithMetaPresenter.php
│       └── 📄 ValidationResultPresenter.php
└── 📂 V1/ (7 files)
    └── 📂 Brand/ (7 files)
        ├── 📂 Action/ (6 files)
        │   ├── 📄 BrandCreateAction.php
        │   ├── 📄 BrandDataAction.php
        │   ├── 📄 BrandDeleteAction.php
        │   ├── 📄 BrandRestoreAction.php
        │   ├── 📄 BrandUpdateAction.php
        │   └── 📄 BrandViewAction.php
        └── 📂 Validation/ (1 file)
            └── 📄 BrandInputValidator.php
```

### 🎯 **Fungsi API Layer:**
- 🌐 **HTTP Request Handling** - Proses request HTTP masuk
- 📤 **Response Formatting** - Format response API (JSON, pagination, error)
- ✅ **Input Validation** - Validasi input request
- 🔐 **Security Middleware** - JWT, CORS, rate limiting
- 📊 **Data Presentation** - Transform data untuk API response

---

## ⚙️ **2. Application Layer (5 files)**

### 📂 Struktur Folder:
```
Application/
├── 📂 Brand/ (4 files)
│   ├── 📄 BrandApplicationService.php
│   ├── 📂 Command/ (2 files)
│   │   ├── 📄 CreateBrandCommand.php
│   │   └── 📄 UpdateBrandCommand.php
│   └── 📂 Dto/ (1 file)
│       └── 📄 BrandResponse.php
└── 📂 Shared/ (1 file)
    └── 📂 Factory/ (1 file)
        └── 📄 DetailInfoFactory.php
```

### 🎯 **Fungsi Application Layer:**
- 🔄 **Use Case Orchestration** - Koordinasi business logic
- 📦 **Command/Query Pattern** - Command objects untuk operations
- 🎯 **DTO Transformation** - Data transfer objects
- 🏭 **Factory Pattern** - Object creation logic
- 📋 **Service Coordination** - Hub antara Domain & Infrastructure

---

## 💻 **3. Console Layer (1 file)**

### 📂 Struktur Folder:
```
Console/
└── 📄 HelloCommand.php
```

### 🎯 **Fungsi Console Layer:**
- 💻 **CLI Commands** - Command line interface
- 🛠️ **Maintenance Tasks** - Background jobs, cleanup
- 📊 **System Administration** - Admin operations via CLI

---

## 🏛️ **4. Domain Layer (14 files)**

### 📂 Struktur Folder:
```
Domain/
├── 📂 Brand/ (3 files)
│   ├── 📄 Entity/Brand.php
│   ├── 📄 Repository/BrandRepositoryInterface.php
│   └── 📄 Service/BrandDomainService.php
└── 📂 Shared/ (11 files)
    ├── 📂 Audit/ (1 file)
    │   └── 📄 AuditServiceInterface.php
    ├── 📂 Concerns/ (5 files)
    │   ├── 📂 Entity/ (4 files)
    │   │   ├── 📄 Auditable.php
    │   │   ├── 📄 Descriptive.php
    │   │   ├── 📄 Identifiable.php
    │   │   └── 📄 Stateful.php
    │   └── 📂 Service/ (1 file)
    │       └── 📄 DomainValidator.php
    ├── 📂 Contract/ (3 files)
    │   ├── 📄 ActorInterface.php
    │   ├── 📄 CurrentUserInterface.php
    │   └── 📄 DateTimeProviderInterface.php
    └── 📂 ValueObject/ (2 files)
        ├── 📄 DetailInfo.php
        └── 📄 Status.php
```

### 🎯 **Fungsi Domain Layer:**
- 🏛️ **Business Logic** - Core business rules
- 📋 **Entity Management** - Domain entities
- 🎯 **Value Objects** - Immutable value objects
- 🔄 **Domain Services** - Business domain services
- 📝 **Repository Interfaces** - Data access contracts
- 🔧 **Traits & Concerns** - Reusable domain behavior

---

## 🔧 **5. Infrastructure Layer (23 files)**

### 📂 Struktur Folder:
```
Infrastructure/
├── 📂 Audit/ (1 file)
│   └── 📄 DatabaseAuditService.php
├── 📂 Clock/ (1 file)
│   └── 📄 SystemClock.php
├── 📂 Concerns/ (2 files)
│   ├── 📄 Auditable.php
│   └── 📄 HasCoreFeatures.php
├── 📂 Monitoring/ (9 files)
│   ├── 📄 CustomMonitoringService.php
│   ├── 📄 ErrorMonitoringMiddleware.php
│   ├── 📄 MetricsMiddleware.php
│   ├── 📄 MonitoringServiceInterface.php
│   ├── 📄 MonologMonitoringService.php
│   ├── 📄 RequestIdMiddleware.php
│   └── 📂 Service/ (3 files)
│       ├── 📄 HealthCheckService.php
│       ├── 📄 MetricsService.php
│       └── 📄 SystemInfoService.php
├── 📂 Persistence/ (1 file)
│   └── 📂 Brand/ (1 file)
│       └── 📄 BrandRepository.php
├── 📂 RateLimit/ (1 file)
│   └── 📄 TokenBucketRateLimiter.php
├── 📂 Security/ (7 files)
│   ├── 📄 AccessChecker.php
│   ├── 📄 Actor.php
│   ├── 📄 ActorProvider.php
│   ├── 📄 CurrentUser.php
│   ├── 📂 Rule/ (1 file)
│   │   └── 📄 PermissionMapRule.php
│   └── 📂 Service/ (2 files)
│       ├── 📄 JwtService.php
│       └── 📄 PasswordService.php
└── 📂 Time/ (1 file)
    └── 📄 AppDateTimeProvider.php
```

### 🎯 **Fungsi Infrastructure Layer:**
- 🗄️ **Data Persistence** - Database repositories
- 🔐 **Security Implementation** - JWT, authentication, authorization
- 📊 **Monitoring & Logging** - System monitoring, metrics
- 🕐 **Time & Clock** - DateTime providers
- 📝 **Audit Trail** - Audit logging implementation
- 🚦 **Rate Limiting** - API rate limiting
- 🔧 **External Services** - Third-party integrations

---

## 🗄️ **6. Migration Layer (3 files)**

### 📂 Struktur Folder:
```
Migration/
├── 📄 M20240101000000CreateBrand.php
├── 📄 M20240101000001CreateAuditLogs.php
└── 📄 M20240101000002CreateUsers.php
```

### 🎯 **Fungsi Migration Layer:**
- 🗄️ **Database Schema** - Table creation & modifications
- 🔄 **Version Control** - Database versioning
- 📊 **Seed Data** - Initial data population
- 🛠️ **Schema Updates** - Incremental database changes

---

## 🛠️ **7. Shared Layer (37 files)**

### 📂 Struktur Folder:
```
Shared/
├──  ApplicationParams.php
├── 📂 Dto/ (2 files)
│   ├── 📄 PaginatedResult.php
│   └── 📄 SearchCriteria.php
├── 📂 Enums/ (2 files)
│   ├── 📄 AppConstants.php
│   └── 📄 RecordStatus.php
├── 📂 ErrorHandler/ (1 file)
│   └── 📄 ErrorJsonRenderer.php
├── 📂 Exception/ (12 files)
│   ├── 📄 BadRequestException.php
│   ├── 📄 ConflictException.php
│   ├── 📄 ForbiddenException.php
│   ├── 📄 HttpException.php
│   ├── 📄 InternalServerErrorException.php
│   ├── 📄 NotFoundException.php
│   ├── 📄 TooManyRequestsException.php
│   ├── 📄 UnauthorizedException.php
│   └── 📂 Validation/ (3 files)
│       ├── 📄 ValidationException.php
│       ├── 📄 ValidationFailedException.php
│       └── 📄 ValidationResult.php
├── 📂 Middleware/ (7 files)
│   ├── 📄 CorsMiddleware.php
│   ├── 📄 JwtMiddleware.php
│   ├── 📄 RateLimitMiddleware.php
│   ├── 📄 RequestLoggingMiddleware.php
│   ├── 📄 RequestParamsMiddleware.php
│   ├── 📄 SecurityHeadersMiddleware.php
│   └── 📄 TimerMiddleware.php
├── 📂 Repository/ (1 file)
│   └── 📄 CoreRepositoryInterface.php
├── 📂 Request/ (5 files)
│   ├── 📄 InputNormalizer.php
│   ├── 📄 Payload.php
│   ├── 📄 RawParams.php
│   ├── 📄 RequestParams.php
│   └── 📄 ValidationContext.php
├── 📂 Security/ (1 file)
│   └── 📄 InputSanitizer.php
├── 📂 Utility/ (2 files)
│   ├── 📄 Arrays.php
│   └── 📄 JsonDataHydrator.php
├── 📂 Validation/ (2 files)
│   ├── 📄 AbstractValidator.php
│   └── 📄 ValidationHelper.php
└── 📂 ValueObject/ (1 file)
    └── 📄 Message.php
```

### 🎯 **Fungsi Shared Layer:**
- 🛠️ **Utility Functions** - Helper functions, array utilities
- 📝 **Validation** - Input validation, sanitization
- 🔐 **Security** - Input sanitization, security helpers
- 🌐 **HTTP Handling** - Request/response processing
- 📊 **DTOs** - Data transfer objects
- 🚨 **Exception Handling** - Custom exceptions
- 🔧 **Middleware** - HTTP middleware stack
- 📋 **Value Objects** - Shared value objects
- 🎯 **Constants & Enums** - Application constants

---

## 📄 **8. Root Files (2 files)**

```
├── 📄 Environment.php
└── 📄 autoload.php
```

### 🎯 **Fungsi Root Files:**
- 🌍 **Environment Configuration** - Environment-specific settings
- 📦 **Autoloader** - Class autoloading configuration

---

## 🏗️ **Arsitektur Pattern**

### 🎯 **Clean Architecture DDD:**
```
┌─────────────────────────────────────────────────────────────┐
│                        API Layer                            │
│  HTTP Requests → Actions → Validators → Presenters          │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                   Application Layer                         │
│  Use Cases → Commands → DTOs → Application Services          │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                     Domain Layer                            │
│  Entities → Value Objects → Domain Services → Interfaces    │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                 Infrastructure Layer                        │
│  Repositories → External Services → Security → Monitoring    │
└─────────────────────────────────────────────────────────────┘
```

### 🔄 **Dependency Flow:**
```
API → Application → Domain ← Infrastructure
      ↑              ↑         ↑
      └──────────────┴─────────┘
            Shared Layer (cross-cutting)
```

### 🎯 **Key Principles:**
- 🏗️ **Domain-Driven Design** - Business logic di Domain layer
- 🔄 **Dependency Inversion** - Interface di Domain, implementation di Infrastructure
- 📦 **Single Responsibility** - Setiap layer punya tanggung jawab spesifik
- 🎨 **Command/Query Separation** - Terpisah antara read & write operations
- 🛡️ **Security First** - Input sanitization, validation, authorization
- 📊 **Observability** - Monitoring, logging, audit trail

---

## 📈 **File Distribution Summary**

| Layer | Files | Percentage | Primary Purpose |
|-------|-------|------------|-----------------|
| 🌐 API | 19 | 34% | HTTP handling & response formatting |
| ⚙️ Application | 5 | 9% | Use case orchestration |
| 🏛️ Domain | 14 | 25% | Business logic & entities |
| 🔧 Infrastructure | 23 | 41% | External integrations & persistence |
| 🗄️ Migration | 3 | 5% | Database schema management |
| 🛠️ Shared | 37 | 66% | Cross-cutting utilities |
| 💻 Console | 1 | 2% | CLI commands |

**📊 Note:** Shared layer files are counted separately as they span across multiple layers.

---

## 🚀 **Quick Reference**

### 🎯 **Entry Points:**
- **HTTP API:** `Api/V1/Brand/Action/*.php`
- **Console:** `Console/HelloCommand.php`
- **Application Services:** `Application/Brand/BrandApplicationService.php`

### 🔧 **Core Components:**
- **Entities:** `Domain/Brand/Entity/Brand.php`
- **Repositories:** `Infrastructure/Persistence/Brand/BrandRepository.php`
- **Security:** `Infrastructure/Security/` (Actor, JWT, Access Control)
- **Validation:** `Shared/Validation/` & `Api/V1/Brand/Validation/`

### 📊 **Configuration:**
- **DI Container:** `config/common/di/*.php`
- **Routes:** `config/common/routes.php`
- **Access Control:** `config/common/access.php`

---

## 📋 **Complete File & Folder Listing**

### 🗂️ **Full Directory Tree:**
```
src/
├── 📂 Api/
│   ├── 📄 IndexAction.php
│   ├── 📂 Shared/
│   │   ├── 📄 ExceptionResponderFactory.php
│   │   ├── 📄 NotFoundMiddleware.php
│   │   ├── 📄 ResponseFactory.php
│   │   └── 📂 Presenter/
│   │       ├── 📄 AsIsPresenter.php
│   │       ├── 📄 CollectionPresenter.php
│   │       ├── 📄 FailPresenter.php
│   │       ├── 📄 OffsetPaginatorPresenter.php
│   │       ├── 📄 PresenterInterface.php
│   │       ├── 📄 SuccessPresenter.php
│   │       ├── 📄 SuccessWithMetaPresenter.php
│   │       └── 📄 ValidationResultPresenter.php
│   └── 📂 V1/
│       └── 📂 Brand/
│           ├── 📂 Action/
│           │   ├── 📄 BrandCreateAction.php
│           │   ├── 📄 BrandDataAction.php
│           │   ├── 📄 BrandDeleteAction.php
│           │   ├── 📄 BrandRestoreAction.php
│           │   ├── 📄 BrandUpdateAction.php
│           │   └── 📄 BrandViewAction.php
│           └── 📂 Validation/
│               └── 📄 BrandInputValidator.php
├── 📂 Application/
│   ├── 📂 Brand/
│   │   ├── 📄 BrandApplicationService.php
│   │   ├── 📂 Command/
│   │   │   ├── 📄 CreateBrandCommand.php
│   │   │   └── 📄 UpdateBrandCommand.php
│   │   └── 📂 Dto/
│   │       └── 📄 BrandResponse.php
│   └── 📂 Shared/
│       └── 📂 Factory/
│           └── 📄 DetailInfoFactory.php
├── 📂 Console/
│   └── 📄 HelloCommand.php
├── 📂 Domain/
│   ├── 📂 Brand/
│   │   ├── 📄 Entity/
│   │   │   └── 📄 Brand.php
│   │   ├── 📄 Repository/
│   │   │   └── 📄 BrandRepositoryInterface.php
│   │   └── 📄 Service/
│   │       └── 📄 BrandDomainService.php
│   └── 📂 Shared/
│       ├── 📂 Audit/
│       │   └── 📄 AuditServiceInterface.php
│       ├── 📂 Concerns/
│       │   ├── 📂 Entity/
│       │   │   ├── 📄 Auditable.php
│       │   │   ├── 📄 Descriptive.php
│       │   │   ├── 📄 Identifiable.php
│       │   │   └── 📄 Stateful.php
│       │   └── 📂 Service/
│       │       └── 📄 DomainValidator.php
│       ├── 📂 Contract/
│       │   ├── 📄 ActorInterface.php
│       │   ├── 📄 CurrentUserInterface.php
│       │   └── 📄 DateTimeProviderInterface.php
│       └── 📂 ValueObject/
│           ├── 📄 DetailInfo.php
│           └── 📄 Status.php
├── 📂 Infrastructure/
│   ├── 📂 Audit/
│   │   └── 📄 DatabaseAuditService.php
│   ├── 📂 Clock/
│   │   └── 📄 SystemClock.php
│   ├── 📂 Concerns/
│   │   ├── 📄 Auditable.php
│   │   └── 📄 HasCoreFeatures.php
│   ├── 📂 Monitoring/
│   │   ├── 📄 CustomMonitoringService.php
│   │   ├── 📄 ErrorMonitoringMiddleware.php
│   │   ├── 📄 MetricsMiddleware.php
│   │   ├── 📄 MonitoringServiceInterface.php
│   │   ├── 📄 MonologMonitoringService.php
│   │   ├── 📄 RequestIdMiddleware.php
│   │   └── 📂 Service/
│   │       ├── 📄 HealthCheckService.php
│   │       ├── 📄 MetricsService.php
│   │       └── 📄 SystemInfoService.php
│   ├── 📂 Persistence/
│   │   └── 📂 Brand/
│   │       └── 📄 BrandRepository.php
│   ├── 📂 RateLimit/
│   │   └── 📄 TokenBucketRateLimiter.php
│   ├── 📂 Security/
│   │   ├── 📄 AccessChecker.php
│   │   ├── 📄 Actor.php
│   │   ├── 📄 ActorProvider.php
│   │   ├── 📄 CurrentUser.php
│   │   ├── 📂 Rule/
│   │   │   └── 📄 PermissionMapRule.php
│   │   └── 📂 Service/
│   │       ├── 📄 JwtService.php
│   │       └── 📄 PasswordService.php
│   └── 📂 Time/
│       └── 📄 AppDateTimeProvider.php
├── 📂 Migration/
│   ├── 📄 M20240101000000CreateBrand.php
│   ├── 📄 M20240101000001CreateAuditLogs.php
│   └── 📄 M20240101000002CreateUsers.php
├── 📂 Shared/
│   ├──  ApplicationParams.php
│   ├── 📂 Dto/
│   │   ├── 📄 PaginatedResult.php
│   │   └── 📄 SearchCriteria.php
│   ├── 📂 Enums/
│   │   ├── 📄 AppConstants.php
│   │   └── 📄 RecordStatus.php
│   ├── 📂 ErrorHandler/
│   │   └── 📄 ErrorJsonRenderer.php
│   ├── 📂 Exception/
│   │   ├── 📄 BadRequestException.php
│   │   ├── 📄 ConflictException.php
│   │   ├── 📄 ForbiddenException.php
│   │   ├── 📄 HttpException.php
│   │   ├── 📄 InternalServerErrorException.php
│   │   ├── 📄 NotFoundException.php
│   │   ├── 📄 TooManyRequestsException.php
│   │   ├── 📄 UnauthorizedException.php
│   │   └── 📂 Validation/
│   │       ├── 📄 ValidationException.php
│   │       ├── 📄 ValidationFailedException.php
│   │       └── 📄 ValidationResult.php
│   ├── 📂 Middleware/
│   │   ├── 📄 CorsMiddleware.php
│   │   ├── 📄 JwtMiddleware.php
│   │   ├── 📄 RateLimitMiddleware.php
│   │   ├── 📄 RequestLoggingMiddleware.php
│   │   ├── 📄 RequestParamsMiddleware.php
│   │   ├── 📄 SecurityHeadersMiddleware.php
│   │   └── 📄 TimerMiddleware.php
│   ├── 📂 Repository/
│   │   └── 📄 CoreRepositoryInterface.php
│   ├── 📂 Request/
│   │   ├── 📄 InputNormalizer.php
│   │   ├── 📄 Payload.php
│   │   ├── 📄 RawParams.php
│   │   ├── 📄 RequestParams.php
│   │   └── 📄 ValidationContext.php
│   ├── 📂 Security/
│   │   └── 📄 InputSanitizer.php
│   ├── 📂 Utility/
│   │   ├── 📄 Arrays.php
│   │   └── 📄 JsonDataHydrator.php
│   ├── 📂 Validation/
│   │   ├── 📄 AbstractValidator.php
│   │   └── 📄 ValidationHelper.php
│   └── 📂 ValueObject/
│       └── 📄 Message.php
├── 📄 Environment.php
└── 📄 autoload.php
```

### 📊 **File Count Summary:**
- **Total Files:** 56 PHP files (reduced from 57)
- **Total Folders:** 37 folders (reduced from 38)
- **Max Depth:** 4 levels deep
- **Root Files:** 2 files (Environment.php, autoload.php)
- **Removed:** Shared/Api/ folder with ApiResourceInterface.php

---

*📅 Documentation generated: 2026-01-23*  
*🏗️ Architecture: Clean Architecture DDD with Yii3 Framework*
