# Complete Shared Classes Installation

## Quick Start

After creating a project from this boilerplate, run:

```bash
composer skeleton-install
composer skeleton-copy-examples
```

## What it does

### `composer skeleton-install`
1. **Copy Shared classes** - Copy all Shared infrastructure from vendor to project
2. **Copy Infrastructure classes** - Copy Infrastructure components from vendor to project
3. **Copy Domain Shared classes** - Copy Domain Shared components from vendor to project
4. **Copy Application Shared classes** - Copy Application Shared components from vendor to project
5. **Copy API Shared classes** - Copy API Shared components from vendor to project
6. **Copy Config files** - Copy configuration files from vendor to project
7. **Copy Message files** - Copy translation files from vendor to project
8. **Copy API files** - Copy API core files from vendor to project
9. **Update composer.json** - Add required packages for Yii3 API functionality
10. **Create directories** - Set up complete directory structure for all layers

### `composer skeleton-copy-examples`
1. **Copy example files** - Copy example configuration and code files
2. **Setup environment** - Create .env from .env.example
3. **Copy example entities** - Copy Example entity across all layers
4. **Copy migrations** - Copy example database migrations
5. **Create flag file** - Prevent re-copying unless forced

## Directory Structure

The script creates the following structure:

### Shared Classes (`src/Shared/`)
```
src/Shared/
├── Dto/           # Data Transfer Objects
├── Enums/         # Enumerations  
├── ErrorHandler/  # Error handling utilities
├── Exception/     # Custom exceptions
├── Middleware/    # HTTP middleware
├── Query/         # Query builders and utilities
├── Repository/    # Repository base classes
├── Request/       # Request handling classes
├── Security/      # Security utilities
├── Utility/       # General utilities
├── Validation/    # Validation classes
└── ValueObject/   # Value objects
```

### Infrastructure Classes (`src/Infrastructure/`)
```
src/Infrastructure/
├── Audit/         # Audit logging components
├── Clock/         # Time/clock utilities
├── Concerns/      # Infrastructure traits
├── Monitoring/    # Monitoring and logging
├── RateLimit/     # Rate limiting components
├── Security/      # Security infrastructure
├── Time/          # Time management
└── Persistence/   # Database persistence (empty directory)
```

### Domain Shared Classes (`src/Domain/Shared/`)
```
src/Domain/Shared/
├── Audit/         # Domain audit components
├── Concerns/      # Domain traits
├── Contract/      # Domain contracts and interfaces
├── Security/      # Domain security components
└── ValueObject/   # Domain value objects
```

### Application Shared Classes (`src/Application/Shared/`)
```
src/Application/Shared/
└── Factory/       # Application factory classes
```

### API Files (`src/`)
```
src/
├── autoload.php                   # API autoloader configuration
├── Api/                          # API layer
│   ├── IndexAction.php           # API index action
│   └── Shared/                   # API shared components
│       ├── Presenter/            # API response presenters
│       │   ├── FailPresenter.php
│       │   ├── SuccessPresenter.php
│       │   └── SuccessWithMetaPresenter.php
│       ├── ExceptionResponderFactory.php  # Exception response factory
│       └── ResponseFactory.php    # HTTP response factory
└── Api/V1/                       # API version 1
    └── Example/                   # Example API endpoints
        ├── Action/                # API actions
        ├── Validation/            # API validation
        └── ...
```

### Example Entity Structure
```
src/
├── Api/V1/Example/              # Example API endpoints
├── Application/Example/          # Example application services
├── Domain/Example/              # Example domain entities
└── Infrastructure/Persistence/Example/  # Example repositories
```

### Config Files (`config/`)
```
config/
├── common/                        # Common configurations
│   ├── middleware.php             # Middleware configuration
│   └── di/                      # Dependency injection
│       ├── access-di.php         # Access control DI
│       ├── audit.php             # Audit configuration
│       ├── db-pgsql.php          # PostgreSQL database DI
│       ├── json.php              # JSON handler DI
│       ├── jwt.php               # JWT configuration
│       ├── middleware.php        # Middleware DI
│       ├── monitoring.php        # Monitoring configuration
│       └── security.php          # Security configuration
└── web/                          # Web configurations
    └── di/                      # Web dependency injection
        └── application.php      # Web application DI
```

### Message Files (`resources/messages/`)
```
resources/messages/
├── en/                           # English translations
│   ├── error.php                 # Error messages
│   ├── success.php               # Success messages
│   └── validation.php           # Validation messages
└── id/                           # Indonesian translations
    ├── error.php                 # Error messages
    ├── success.php               # Success messages
    └── validation.php           # Validation messages
```

## Manual Setup

If you prefer to set up manually:

1. Copy Shared classes from vendor:
   ```bash
   cp -r vendor/rahmatsyaparudin/yii3-api-boilerplate/src/Shared src/
   ```

2. Copy Infrastructure classes from vendor:
   ```bash
   cp -r vendor/rahmatsyaparudin/yii3-api-boilerplate/src/Infrastructure src/
   ```

3. Copy Domain Shared classes from vendor:
   ```bash
   cp -r vendor/rahmatsyaparudin/yii3-api-boilerplate/src/Domain/Shared src/Domain/
   ```

4. Copy Application Shared classes from vendor:
   ```bash
   cp -r vendor/rahmatsyaparudin/yii3-api-boilerplate/src/Application/Shared src/Application/
   ```

5. Copy API Shared classes from vendor:
   ```bash
   cp -r vendor/rahmatsyaparudin/yii3-api-boilerplate/src/Api/Shared src/Api/
   ```

6. Copy API files from vendor:
   ```bash
   cp vendor/rahmatsyaparudin/yii3-api-boilerplate/src/Api/autoload.php src/Api/
   cp vendor/rahmatsyaparudin/yii3-api-boilerplate/src/Api/IndexAction.php src/Api/
   ```

7. Copy Config files from vendor:
   ```bash
   cp vendor/rahmatsyaparudin/yii3-api-boilerplate/config/common/middleware.php config/common/
   cp -r vendor/rahmatsyaparudin/yii3-api-boilerplate/config/common/di config/common/di/
   cp vendor/rahmatsyaparudin/yii3-api-boilerplate/config/web/di/application.php config/web/di/
   ```

8. Copy Message files from vendor:
   ```bash
   cp -r vendor/rahmatsyaparudin/yii3-api-boilerplate/resources/messages resources/
   ```

9. Copy Example files from vendor:
   ```bash
   cp vendor/rahmatsyaparudin/yii3-api-boilerplate/.env.example .env
   cp vendor/rahmatsyaparudin/yii3-api-boilerplate/resources/messages/en/app.php resources/messages/en/
   cp vendor/rahmatsyaparudin/yii3-api-boilerplate/resources/messages/id/app.php resources/messages/id/
   cp vendor/rahmatsyaparudin/yii3-api-boilerplate/config/common/access.php config/common/
   cp vendor/rahmatsyaparudin/yii3-api-boilerplate/config/common/params.php config/common/
   cp vendor/rahmatsyaparudin/yii3-api-boilerplate/config/common/routes.php config/common/
   cp -r vendor/rahmatsyaparudin/yii3-api-boilerplate/config/common/di/infrastructure.php config/common/di/
   cp -r vendor/rahmatsyaparudin/yii3-api-boilerplate/config/common/di/repository.php config/common/di/
   cp -r vendor/rahmatsyaparudin/yii3-api-boilerplate/config/common/di/service.php config/common/di/
   cp -r vendor/rahmatsyaparudin/yii3-api-boilerplate/config/common/di/translator.php config/common/di/
   cp -r vendor/rahmatsyaparudin/yii3-api-boilerplate/src/Api/V1/Example src/Api/V1/
   cp -r vendor/rahmatsyaparudin/yii3-api-boilerplate/src/Application/Example src/Application/
   cp -r vendor/rahmatsyaparudin/yii3-api-boilerplate/src/Domain/Example src/Domain/
   cp -r vendor/rahmatsyaparudin/yii3-api-boilerplate/src/Infrastructure/Persistence/Example src/Infrastructure/Persistence/
   cp -r vendor/rahmatsyaparudin/yii3-api-boilerplate/src/Migration src/
   ```

10. Create required directories:
    ```bash
    mkdir -p src/Shared/{Dto,Enums,ErrorHandler,Exception,Middleware,Query,Repository,Request,Security,Utility,Validation,ValueObject}
    mkdir -p src/Infrastructure/{Audit,Clock,Concerns,Monitoring,RateLimit,Security,Time,Persistence}
    mkdir -p src/Domain/Shared/{Audit,Concerns,Contract,Security,ValueObject}
    mkdir -p src/Application/Shared/Factory
    mkdir -p src/Api/Shared/Presenter
    mkdir -p config/common/di
    mkdir -p config/web/di
    mkdir -p resources/messages/{en,id}
    ```

## Available Commands

After installation:

```bash
# Start development server
composer run serve

# Run migrations
composer run migrate

# Generate CRUD for entities
php yii simple-generate crud Product

# Generate complete project from template
php yii template:generate my-api /tmp

# Copy example files (if not already copied)
composer skeleton-copy-examples

# Force re-copy examples (remove flag file first)
rm .skeleton_examples_copied && composer skeleton-copy-examples
```

## Next Steps

After installing all Shared classes, Config files, Message files, and Examples:

1. Install required packages:
   ```bash
   composer update
   ```

2. Configure your `.env` file with your database settings

3. Run database migrations:
   ```bash
   php yii migrate
   ```

4. Start development with:
   ```bash
   php yii serve
   ```

Your project now has complete Shared infrastructure, example entities, and all required packages ready for development!

## Workflow Summary

```bash
# 1. Create project from boilerplate
composer create-project rahmatsyaparudin/yii3-api-boilerplate my-api
cd my-api

# 2. Install shared infrastructure
composer skeleton-install

# 3. Copy example files and entities
composer skeleton-copy-examples

# 4. Install required packages
composer update

# 5. Setup and run
php yii migrate
php yii serve
```

This gives you a complete Yii3 API project with:
- ✅ Shared infrastructure across all layers
- ✅ Example entity implementation (Example CRUD)
- ✅ Complete configuration files
- ✅ Translation files
- ✅ Database migrations
- ✅ All required Yii3 packages
- ✅ Ready-to-run API endpoints
