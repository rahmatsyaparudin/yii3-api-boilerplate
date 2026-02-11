# Security Guide

## 📋 Overview

Security utilities provide essential security functions for the Yii3 API application, including input sanitization, data validation, and protection against common security vulnerabilities.

---

## 🏗️ Security Architecture

### Directory Structure

```
src/Shared/Security/
└── InputSanitizer.php    # Input sanitization and validation
```

### Design Principles

#### **1. **Defense in Depth**
- Multiple layers of security validation
- Comprehensive input sanitization
- Protection against various attack vectors

#### **2. **Fail-Safe Defaults**
- Secure by default configuration
- Conservative validation rules
- Safe fallback behavior

#### **3. **Performance Optimization**
- Efficient sanitization algorithms
- Minimal overhead for security checks
- Cached validation results where appropriate

#### **4. **Extensibility**
- Configurable security rules
- Custom sanitization filters
- Pluginable validation system

---

## 📁 Security Components

### 1. InputSanitizer

**Purpose**: Comprehensive input sanitization and validation

```php
<?php

declare(strict_types=1);

namespace App\Shared\Security;

use App\Shared\Exception\BadRequestException;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Input Sanitizer
 */
final class InputSanitizer
{
    public function __construct(
        private TranslatorInterface $translator,
        private array $config = []
    ) {}

    /**
     * Sanitize input data
     */
    public function sanitize(array $data, array $rules = []): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            $fieldRules = $rules[$key] ?? $this->getDefaultRules($key);
            $sanitized[$key] = $this->sanitizeValue($value, $fieldRules, $key);
        }
        
        return $sanitized;
    }

    /**
     * Sanitize single value
     */
    public function sanitizeValue(mixed $value, array $rules, string $fieldName = ''): mixed
    {
        // Handle null values
        if ($value === null) {
            return $this->handleNullValue($rules, $fieldName);
        }

        // Handle arrays
        if (is_array($value)) {
            return $this->sanitizeArray($value, $rules, $fieldName);
        }

        // Handle strings
        if (is_string($value)) {
            return $this->sanitizeString($value, $rules, $fieldName);
        }

        // Handle numbers
        if (is_numeric($value)) {
            return $this->sanitizeNumber($value, $rules, $fieldName);
        }

        // Handle booleans
        if (is_bool($value)) {
            return $this->sanitizeBoolean($value, $rules, $fieldName);
        }

        // Unknown type - apply strict validation
        return $this->sanitizeUnknown($value, $rules, $fieldName);
    }

    /**
     * Sanitize string value
     */
    private function sanitizeString(string $value, array $rules, string $fieldName): string
    {
        // Apply encoding rules
        $value = $this->applyEncoding($value, $rules);

        // Apply filtering rules
        $value = $this->applyFilters($value, $rules);

        // Apply validation rules
        $this->validateString($value, $rules, $fieldName);

        return $value;
    }

    /**
     * Apply encoding rules
     */
    private function applyEncoding(string $value, array $rules): string
    {
        // HTML encoding
        if ($rules['encode_html'] ?? true) {
            $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // URL encoding
        if ($rules['encode_url'] ?? false) {
            $value = urlencode($value);
        }

        // Base64 encoding (for specific use cases)
        if ($rules['encode_base64'] ?? false) {
            $value = base64_encode($value);
        }

        return $value;
    }

    /**
     * Apply filtering rules
     */
    private function applyFilters(string $value, array $rules): string
    {
        // Trim whitespace
        if ($rules['trim'] ?? true) {
            $value = trim($value);
        }

        // Remove control characters
        if ($rules['remove_control_chars'] ?? true) {
            $value = $this->removeControlCharacters($value);
        }

        // Remove HTML tags
        if ($rules['strip_tags'] ?? false) {
            $value = strip_tags($value);
        }

        // Normalize whitespace
        if ($rules['normalize_whitespace'] ?? false) {
            $value = preg_replace('/\s+/', ' ', $value);
        }

        // Remove special characters
        if (isset($rules['allowed_chars'])) {
            $value = $this->filterCharacters($value, $rules['allowed_chars']);
        }

        // Apply custom regex filters
        if (isset($rules['regex_filters'])) {
            $value = $this->applyRegexFilters($value, $rules['regex_filters']);
        }

        return $value;
    }

    /**
     * Validate string value
     */
    private function validateString(string $value, array $rules, string $fieldName): void
    {
        // Length validation
        if (isset($rules['min_length']) && strlen($value) < $rules['min_length']) {
            throw BadRequestException::invalidParameter(
                $fieldName,
                "Minimum length is {$rules['min_length']} characters"
            );
        }

        if (isset($rules['max_length']) && strlen($value) > $rules['max_length']) {
            throw BadRequestException::invalidParameter(
                $fieldName,
                "Maximum length is {$rules['max_length']} characters"
            );
        }

        // Pattern validation
        if (isset($rules['pattern']) && !preg_match($rules['pattern'], $value)) {
            throw BadRequestException::invalidParameter(
                $fieldName,
                'Invalid format'
            );
        }

        // Email validation
        if (($rules['email'] ?? false) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw BadRequestException::invalidParameter(
                $fieldName,
                'Invalid email format'
            );
        }

        // URL validation
        if (($rules['url'] ?? false) && !filter_var($value, FILTER_VALIDATE_URL)) {
            throw BadRequestException::invalidParameter(
                $fieldName,
                'Invalid URL format'
            );
        }

        // IP validation
        if (($rules['ip'] ?? false) && !filter_var($value, FILTER_VALIDATE_IP)) {
            throw BadRequestException::invalidParameter(
                $fieldName,
                'Invalid IP address'
            );
        }

        // SQL injection detection
        if ($rules['detect_sql_injection'] ?? true) {
            $this->detectSqlInjection($value, $fieldName);
        }

        // XSS detection
        if ($rules['detect_xss'] ?? true) {
            $this->detectXss($value, $fieldName);
        }
    }

    /**
     * Sanitize array value
     */
    private function sanitizeArray(array $value, array $rules, string $fieldName): array
    {
        $sanitized = [];
        $maxItems = $rules['max_array_items'] ?? 100;

        if (count($value) > $maxItems) {
            throw BadRequestException::invalidParameter(
                $fieldName,
                "Maximum {$maxItems} items allowed"
            );
        }

        foreach ($value as $index => $item) {
            $sanitized[$index] = $this->sanitizeValue($item, $rules, "{$fieldName}[{$index}]");
        }

        return $sanitized;
    }

    /**
     * Sanitize number value
     */
    private function sanitizeNumber(mixed $value, array $rules, string $fieldName): int|float
    {
        // Convert to appropriate type
        $number = is_string($value) && is_numeric($value) 
            ? (str_contains($value, '.') ? (float) $value : (int) $value)
            : $value;

        // Range validation
        if (isset($rules['min']) && $number < $rules['min']) {
            throw BadRequestException::invalidParameter(
                $fieldName,
                "Minimum value is {$rules['min']}"
            );
        }

        if (isset($rules['max']) && $number > $rules['max']) {
            throw BadRequestException::invalidParameter(
                $fieldName,
                "Maximum value is {$rules['max']}"
            );
        }

        // Integer validation
        if (($rules['integer'] ?? false) && !is_int($number)) {
            throw BadRequestException::invalidParameter(
                $fieldName,
                'Value must be an integer'
            );
        }

        // Positive validation
        if (($rules['positive'] ?? false) && $number <= 0) {
            throw BadRequestException::invalidParameter(
                $fieldName,
                'Value must be positive'
            );
        }

        return $number;
    }

    /**
     * Sanitize boolean value
     */
    private function sanitizeBoolean(bool $value, array $rules, string $fieldName): bool
    {
        // Boolean values are generally safe
        return $value;
    }

    /**
     * Sanitize unknown type
     */
    private function sanitizeUnknown(mixed $value, array $rules, string $fieldName): mixed
    {
        // Strict validation for unknown types
        if ($rules['strict'] ?? true) {
            throw BadRequestException::invalidParameter(
                $fieldName,
                'Invalid data type'
            );
        }

        return $value;
    }

    /**
     * Handle null value
     */
    private function handleNullValue(array $rules, string $fieldName): mixed
    {
        if ($rules['required'] ?? false) {
            throw BadRequestException::invalidParameter(
                $fieldName,
                'This field is required'
            );
        }

        return null;
    }

    /**
     * Remove control characters
     */
    private function removeControlCharacters(string $value): string
    {
        // Remove all control characters except newlines and tabs
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
    }

    /**
     * Filter characters
     */
    private function filterCharacters(string $value, string $allowedChars): string
    {
        $pattern = '/[^' . preg_quote($allowedChars, '/') . ']/';
        return preg_replace($pattern, '', $value);
    }

    /**
     * Apply regex filters
     */
    private function applyRegexFilters(string $value, array $filters): string
    {
        foreach ($filters as $pattern => $replacement) {
            $value = preg_replace($pattern, $replacement, $value);
        }

        return $value;
    }

    /**
     * Detect SQL injection
     */
    private function detectSqlInjection(string $value, string $fieldName): void
    {
        $patterns = [
            '/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|EXEC|UNION|SCRIPT)\b)/i',
            '/(\b(OR|AND)\s+\d+\s*=\s*\d+)/i',
            '/(\b(OR|AND)\s+\'\w+\'\s*=\s*\'\w+\')/i',
            '/(\-\-|\#|\/\*|\*\/)/',
            '/(\b(LOAD_FILE|INTO\s+OUTFILE|DUMPFILE)\b)/i',
            '/(\b(HEX|CHAR|CONCAT|CONCAT_WS)\s*\()/i',
            '/(\b(BENCHMARK|SLEEP|WAITFOR)\s*\()/i',
            '/(\b(INFORMATION_SCHEMA|SYS|MASTER|MSDB)\b)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                throw BadRequestException::invalidParameter(
                    $fieldName,
                    'Potentially dangerous content detected'
                );
            }
        }
    }

    /**
     * Detect XSS
     */
    private function detectXss(string $value, string $fieldName): void
    {
        $patterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/mi',
            '/<object\b[^<]*(?:(?!<\/object>)<[^<]*)*<\/object>/mi',
            '/<embed\b[^<]*(?:(?!<\/embed>)<[^<]*)*<\/embed>/mi',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload\s*=/i',
            '/onerror\s*=/i',
            '/onclick\s*=/i',
            '/onmouseover\s*=/i',
            '/onfocus\s*=/i',
            '/onblur\s*=/i',
            '/onchange\s*=/i',
            '/onsubmit\s*=/i',
            '/<\s*img[^>]*src\s*=\s*["\']?(?:javascript|vbscript):/i',
            '/<\s*a[^>]*href\s*=\s*["\']?(?:javascript|vbscript):/i',
            '/<\s*meta[^>]*http-equiv\s*=\s*["\']?refresh/i',
            '/<\s*meta[^>]*content\s*=\s*["\']?\d+;\s*url/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                throw BadRequestException::invalidParameter(
                    $fieldName,
                    'Potentially dangerous content detected'
                );
            }
        }
    }

    /**
     * Get default rules for field
     */
    private function getDefaultRules(string $fieldName): array
    {
        // Field-specific default rules
        if (str_contains($fieldName, 'email')) {
            return [
                'trim' => true,
                'max_length' => 255,
                'email' => true,
                'encode_html' => true,
            ];
        }

        if (str_contains($fieldName, 'url')) {
            return [
                'trim' => true,
                'max_length' => 2048,
                'url' => true,
                'encode_html' => true,
            ];
        }

        if (str_contains($fieldName, 'password')) {
            return [
                'min_length' => 8,
                'max_length' => 128,
                'encode_html' => false, // Don't encode passwords
            ];
        }

        if (str_contains($fieldName, 'id') || str_contains($fieldName, '_id')) {
            return [
                'integer' => true,
                'positive' => true,
                'max' => PHP_INT_MAX,
            ];
        }

        if (str_contains($fieldName, 'status')) {
            return [
                'trim' => true,
                'max_length' => 50,
                'allowed_chars' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_',
                'encode_html' => true,
            ];
        }

        // Default rules
        return [
            'trim' => true,
            'max_length' => 255,
            'encode_html' => true,
            'detect_sql_injection' => true,
            'detect_xss' => true,
        ];
    }

    /**
     * Sanitize file upload
     */
    public function sanitizeFileUpload(array $file, array $rules = []): array
    {
        $defaultRules = [
            'max_size' => 10 * 1024 * 1024, // 10MB
            'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
            'allowed_mime_types' => [
                'image/jpeg',
                'image/png',
                'image/gif',
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ],
            'scan_content' => true,
        ];

        $rules = array_merge($defaultRules, $rules);

        // Validate file size
        if ($file['size'] > $rules['max_size']) {
            throw BadRequestException::invalidParameter(
                'file',
                'File size exceeds maximum allowed size'
            );
        }

        // Validate file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $rules['allowed_types'], true)) {
            throw BadRequestException::invalidParameter(
                'file',
                'File type not allowed'
            );
        }

        // Validate MIME type
        if (isset($file['type']) && !in_array($file['type'], $rules['allowed_mime_types'], true)) {
            throw BadRequestException::invalidParameter(
                'file',
                'File MIME type not allowed'
            );
        }

        // Scan file content for malicious content
        if ($rules['scan_content'] && isset($file['tmp_name'])) {
            $this->scanFileContent($file['tmp_name']);
        }

        // Sanitize filename
        $sanitizedName = $this->sanitizeFileName($file['name']);

        return [
            'name' => $sanitizedName,
            'type' => $file['type'],
            'size' => $file['size'],
            'tmp_name' => $file['tmp_name'],
            'extension' => $extension,
        ];
    }

    /**
     * Scan file content
     */
    private function scanFileContent(string $filePath): void
    {
        // Check if file exists
        if (!file_exists($filePath)) {
            return;
        }

        // Read file content
        $content = file_get_contents($filePath);
        if ($content === false) {
            return;
        }

        // Check for malicious patterns
        $maliciousPatterns = [
            '/<\?php/i',
            '/<script/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/<%\s*=/i',
            '/<%\s*@/i',
            '/eval\s*\(/i',
            '/exec\s*\(/i',
            '/system\s*\(/i',
            '/shell_exec\s*\(/i',
            '/passthru\s*\(/i',
        ];

        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                throw BadRequestException::invalidParameter(
                    'file',
                    'File contains potentially malicious content'
                );
            }
        }
    }

    /**
     * Sanitize filename
     */
    private function sanitizeFileName(string $filename): string
    {
        // Remove path information
        $filename = basename($filename);

        // Remove dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);

        // Limit length
        $filename = substr($filename, 0, 255);

        // Ensure filename is not empty
        if (empty($filename)) {
            $filename = 'upload_' . uniqid();
        }

        return $filename;
    }

    /**
     * Generate secure token
     */
    public function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Hash password
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3,
        ]);
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Generate secure hash
     */
    public function generateHash(string $data, string $salt = ''): string
    {
        return hash_hmac('sha256', $data . $salt, $this->getSecretKey());
    }

    /**
     * Get secret key
     */
    private function getSecretKey(): string
    {
        return $this->config['secret_key'] ?? 'default-secret-key-change-in-production';
    }
}
```

**Usage Example**:
```php
// In controller
final class ExampleController
{
    public function __construct(
        private InputSanitizer $sanitizer,
        private ExampleApplicationService $service
    ) {}

    public function actionCreate(): array
    {
        $data = $this->request->getParsedBody();
        
        // Define custom rules
        $rules = [
            'name' => [
                'required' => true,
                'min_length' => 2,
                'max_length' => 100,
                'pattern' => '/^[a-zA-Z\s]+$/',
            ],
            'email' => [
                'required' => true,
                'email' => true,
                'max_length' => 255,
            ],
            'age' => [
                'integer' => true,
                'positive' => true,
                'min' => 0,
                'max' => 120,
            ],
            'bio' => [
                'max_length' => 1000,
                'encode_html' => true,
                'detect_xss' => true,
            ],
        ];

        // Sanitize input
        $sanitizedData = $this->sanitizer->sanitize($data, $rules);

        // Create resource
        $result = $this->service->create($sanitizedData);
        
        return $result->toArray();
    }

    public function actionUpload(): array
    {
        $files = $this->request->getUploadedFiles();
        $file = $files['file'] ?? null;

        if ($file === null) {
            throw BadRequestException::missingParameter('file');
        }

        // Sanitize file upload
        $sanitizedFile = $this->sanitizer->sanitizeFileUpload($file->toArray());
        
        // Process file
        $result = $this->service->uploadFile($sanitizedFile);
        
        return $result->toArray();
    }
}
```

---

## 🔧 Security Configuration

### 1. **DI Configuration**
```php
// config/web/di/security.php
return [
    InputSanitizer::class => [
        '__construct()' => [
            'translator' => Reference::to(TranslatorInterface::class),
            'config' => [
                'secret_key' => getenv('APP_SECRET_KEY'),
                'max_file_size' => 10 * 1024 * 1024, // 10MB
                'allowed_file_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
            ],
        ],
    ],
];
```

### 2. **Security Rules Configuration**
```php
// config/security/rules.php
return [
    'user' => [
        'name' => [
            'required' => true,
            'min_length' => 2,
            'max_length' => 100,
            'pattern' => '/^[a-zA-Z\s]+$/',
            'encode_html' => true,
        ],
        'email' => [
            'required' => true,
            'email' => true,
            'max_length' => 255,
            'encode_html' => true,
        ],
        'password' => [
            'required' => true,
            'min_length' => 8,
            'max_length' => 128,
            'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
        ],
    ],
    'content' => [
        'title' => [
            'required' => true,
            'min_length' => 5,
            'max_length' => 255,
            'encode_html' => true,
            'detect_xss' => true,
        ],
        'body' => [
            'required' => true,
            'min_length' => 10,
            'max_length' => 10000,
            'encode_html' => false, // Allow HTML in content
            'detect_xss' => true,
            'strip_tags' => false,
        ],
    ],
];
```

---

## 🚀 Best Practices

### 1. **Input Validation**
```php
// ✅ Validate all inputs
$sanitizedData = $this->sanitizer->sanitize($data, $rules);

// ❌ Trust user input
$name = $_POST['name'];
```

### 2. **File Upload Security**
```php
// ✅ Validate file uploads
$sanitizedFile = $this->sanitizer->sanitizeFileUpload($file);

// ❌ Accept any file
move_uploaded_file($file['tmp_name'], $destination);
```

### 3. **Password Security**
```php
// ✅ Use secure password hashing
$hash = $this->sanitizer->hashPassword($password);

// ❌ Use weak hashing
$hash = md5($password);
```

### 4. **Token Generation**
```php
// ✅ Use cryptographically secure tokens
$token = $this->sanitizer->generateToken(32);

// ❌ Use predictable tokens
$token = uniqid();
```

---

## 📊 Security Considerations

### 1. **Common Vulnerabilities**
- **SQL Injection**: Detected and blocked
- **XSS**: Detected and prevented
- **CSRF**: Use CSRF tokens
- **File Upload**: Comprehensive validation
- **Path Traversal**: Filename sanitization

### 2. **Data Protection**
- **Encryption**: Sensitive data encryption
- **Hashing**: Secure password hashing
- **Sanitization**: Input data cleaning
- **Validation**: Comprehensive data validation

### 3. **Monitoring**
- **Logging**: Security event logging
- **Alerting**: Suspicious activity detection
- **Auditing**: Security audit trails

---

## 🎯 Summary

Security utilities provide comprehensive protection for the Yii3 API application. Key benefits include:

- **🛡️ Protection**: Defense against common attacks
- **🔍 Validation**: Comprehensive input validation
- **🧹 Sanitization**: Data cleaning and normalization
- **🔐 Encryption**: Secure data handling
- **📁 File Security**: Safe file upload handling
- **🚀 Performance**: Efficient security processing

By following the patterns and best practices outlined in this guide, you can build secure, robust applications with the Yii3 API framework! 🚀
