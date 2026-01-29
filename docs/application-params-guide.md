# Application Parameters Guide

## 📋 Overview

Application Parameters provide centralized configuration management for the Yii3 API application. This component ensures consistent parameter access and type safety across the application.

---

## 🏗️ Architecture

### Design Principles
- **Centralization**: Single source of truth for application parameters
- **Type Safety**: Strong typing with PHP 8+ readonly properties
- **Flexibility**: Configurable default values and environment-specific overrides
- **Performance**: Efficient parameter access with minimal overhead

---

## 📁 Component: ApplicationParams

### Core Functionality

#### **1. Application Information**
- **getName()**: Get application name
- **getVersion()**: Get application version
- **getEnvironment()**: Get current environment (production/development/testing)
- **isDebug()**: Check if debug mode is enabled
- **getApplicationInfo()**: Get complete application information array

#### **2. Localization Support**
- **getLocale()**: Get current locale
- **getSupportedLocales()**: Get array of supported locales
- **isLocaleSupported()**: Check if a locale is supported
- **getDefaultCurrency()**: Get default currency
- **isCurrencySupported()**: Check if currency is supported

#### **3. File Upload Configuration**
- **getMaxFileSize()**: Get maximum file size in bytes
- **getMaxFileSizeFormatted()**: Get file size in human readable format
- **getAllowedFileTypes()**: Get allowed file extensions
- **isFileTypeAllowed()**: Check if file type is permitted

#### **4. Security Settings**
- **getPasswordRequirements()**: Get password length requirements
- **isPasswordValid()**: Validate password against requirements
- **getLoginSecuritySettings()**: Get login attempt limits and lockout settings
- **getRateLimitSettings()**: Get rate limiting configuration

#### **5. Feature Flags**
- **isEmailVerificationRequired()**: Check if email verification is required
- **isRegistrationEnabled()**: Check if user registration is enabled
- **isSocialLoginEnabled()**: Check if social login is available
- **getFeatureFlags()**: Get all feature flags as array

#### **6. Date/Time Configuration**
- **getDateFormatSettings()**: Get date/time format preferences
- **getTimezone()**: Get application timezone
- **getSupportedCurrencies()**: Get supported currency codes

#### **7. API Configuration**
- **getApiRateLimits()**: Get rate limiting rules per endpoint
- **getApiRateLimit()**: Get rate limit for specific endpoint
- **getCorsSettings()**: Get CORS configuration
- **getSecuritySettings()**: Get security headers configuration

#### **8. System Configuration**
- **getCacheSettings()**: Get cache TTL and prefix settings
- **getDatabaseSettings()**: Get database connection settings
- **getLoggingSettings()**: Get logging configuration
- **getMonitoringSettings()**: Get monitoring and metrics settings

#### **9. Environment Detection**
- **isProduction()**: Check if running in production
- **isDevelopment()**: Check if running in development
- **isTesting()**: Check if running in testing environment

#### **10. **Data Export**
- **toArray()**: Convert all parameters to array
- **toJson()**: Convert parameters to JSON string

#### **11. **Factory Methods**
- **fromEnvironment()**: Create from environment variables
- **fromArray()**: Create from configuration array
- **empty()**: Create empty instance

---

## 🔧 Integration Examples

### Controller Usage
```php
final class InfoController
{
    public function __construct(private ApplicationParams $params) {}

    public function actionInfo(): array
    {
        return [
            'application' => $this->params->getApplicationInfo(),
            'features' => $this->params->getFeatureFlags(),
            'limits' => [
                'max_file_size' => $this->params->getMaxFileSizeFormatted(),
                'allowed_file_types' => $this->params->getAllowedFileTypes(),
            ],
        ];
    }
}
```

### Service Usage
```php
final class UserService
{
    public function __construct(private ApplicationParams $params) {}

    public function create(CreateUserCommand $command): UserResponse
    {
        // Check registration enabled
        if (!$this->params->isRegistrationEnabled()) {
            throw new ForbiddenException('Registration is disabled');
        }

        // Validate password
        if (!$this->params->isPasswordValid($command->password)) {
            throw ValidationException('Password requirements not met');
        }

        // Create user logic...
    }
}
```

### Middleware Usage
```php
final class RateLimitMiddleware
{
    public function __construct(private ApplicationParams $params) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $endpoint = $this->getEndpoint($request);
        $rateLimit = $this->params->getApiRateLimit($endpoint);
        
        // Rate limiting logic...
    }
}
```

---

## 🚀 Best Practices

### Parameter Access
```php
// ✅ Use method access
$maxSize = $this->params->getMaxFileSize();

// ✅ Check conditions
if ($this->params->isFileTypeAllowed($extension)) {
    // Process file
}

// ✅ Get configuration arrays
$corsSettings = $this->params->getCorsSettings();
```

### Configuration
```php
// ✅ Environment-based
$params = ApplicationParams::fromEnvironment();

// ✅ Array-based
$params = ApplicationParams::fromArray($config);
```

### Validation
```php
// ✅ Built-in validation
if (!$this->params->isPasswordValid($password)) {
    throw ValidationException('Invalid password');
}

// ✅ Feature flag checks
if ($this->params->isEmailVerificationRequired()) {
    $this->sendVerificationEmail($user);
}
```

---

## 📊 Performance Considerations

- **Initialization**: Parameters initialized once, shared across application
- **Memory Usage**: Single instance with readonly properties
- **Access Speed**: Direct property access with minimal overhead
- **Configuration**: Environment variables loaded at startup

---

## 🎯 Summary

Application Parameters provide centralized, type-safe configuration management with key benefits:

- **🎯 Centralization**: Single source of truth for all configuration
- **🛡️ Type Safety**: Strong typing prevents configuration errors
- **🔄 Immutability**: Readonly properties ensure consistency
- **🌐 Environment Support**: Easy environment-specific configuration
- **📦 Integration**: Seamless DI integration
- **⚡ Performance**: Fast access with minimal overhead

By following these patterns, you can build robust, maintainable configuration management for your Yii3 API application! 🚀
