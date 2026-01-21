# Yii3 API Documentation v1.0

## 📋 Table of Contents

### 🏗️ **Architecture Overview**
- [Domain-Driven Design (DDD)](architecture/ddd.md)
- [Layer Architecture](architecture/layers.md)
- [Design Patterns](architecture/patterns.md)

### 🎯 **Domain Layer**
- [Brand Domain](domain/brand.md)
- [Shared Domain](domain/shared.md)
- [Value Objects](domain/value-objects.md)
- [Entities](domain/entities.md)

### 🌐 **Application Layer**
- [Brand Application Service](application/brand-service.md)
- [Use Cases](application/use-cases.md)
- [DTOs](application/dtos.md)

### 🔌 **API Layer**
- [Brand Endpoints](api/brand-endpoints.md)
- [Request/Response](api/request-response.md)
- [Validation](api/validation.md)
- [Error Handling](api/error-handling.md)

### 🗄️ **Infrastructure Layer**
- [Brand Repository](infrastructure/brand-repository.md)
- [Database Schema](infrastructure/database.md)
- [Security](infrastructure/security.md)

### 📦 **Shared Layer**
- [Message ValueObject](shared/message.md)
- [Request Parameters](shared/request-params.md)
- [Pagination](shared/pagination.md)
- [Validation](shared/validation.md)

### 🔧 **Development**
- [Getting Started](development/getting-started.md)
- [Coding Standards](development/coding-standards.md)
- [Testing](development/testing.md)
- [Deployment](development/deployment.md)

### 📊 **API Reference**
- [Brand API](api/brand-api.md)
- [Authentication](api/auth.md)
- [Error Codes](api/error-codes.md)
- [Response Formats](api/response-formats.md)

---

## 🚀 Quick Start

### Prerequisites
- PHP 8.1+
- Composer
- MySQL/PostgreSQL
- Docker (optional)

### Installation
```bash
git clone <repository>
cd yii3-api
composer install
cp .env.example .env
# Configure database
php yii migrate
```

### Basic Usage
```php
// Create Brand
POST /api/v1/brands
{
    "name": "My Brand",
    "status": "active"
}

// List Brands
GET /api/v1/brands?page=1&page_size=20

// Get Brand
GET /api/v1/brands/{id}

// Update Brand
PUT /api/v1/brands/{id}
{
    "name": "Updated Brand"
}

// Delete Brand
DELETE /api/v1/brands/{id}
```

---

## 📚 Documentation Structure

This documentation is organized by architectural layers following Domain-Driven Design principles:

- **Domain Layer**: Business logic and entities
- **Application Layer**: Use cases and application services  
- **API Layer**: HTTP endpoints and request/response handling
- **Infrastructure Layer**: Data persistence and external services
- **Shared Layer**: Common utilities and cross-cutting concerns

Each section contains detailed explanations, code examples, and best practices.

---

## 🤝 Contributing

1. Follow the [Coding Standards](development/coding-standards.md)
2. Update relevant documentation when making changes
3. Test your changes thoroughly
4. Submit pull requests with clear descriptions

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](../LICENSE) file for details.
