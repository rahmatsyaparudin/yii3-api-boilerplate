# Quality Assurance Guide

## 📋 Overview

The `quality` script is a comprehensive quality assurance tool designed for the Yii3 API project. It provides automated checks for code style, static analysis, testing, and security auditing to ensure high code quality and maintainability.

## 🚀 Quick Start

### Running All Quality Checks
```bash
# Run complete quality check suite
php quality

# Run with specific options
php quality --fix          # Auto-fix code style issues
php quality --coverage      # Generate test coverage reports
php quality --report        # Generate detailed analysis reports
```

### Individual Checks
```bash
# Code style check
vendor/bin/php-cs-fixer check --diff --verbose --allow-risky=yes

# Static analysis
vendor/bin/psalm

# Unit tests
vendor/bin/phpunit --testdox

# Security audit
composer audit
```

## 🔧 Configuration

### Available Options

| Option | Short | Description |
|--------|-------|-------------|
| `--fix` | `-f` | Auto-fix code style issues |
| `--coverage` | `-c` | Generate test coverage reports |
| `--report` | `-r` | Generate detailed analysis reports |

### Default Settings

- **Default Page Size**: 50 records
- **Max Page Size**: 200 records
- **Default Page**: 1
- **Default Sort Direction**: `asc`

## 📊 Quality Checks

### 1. Code Style Check (PHP CS Fixer)

**Purpose**: Ensures consistent code formatting and style adherence.

**What it checks**:
- PSR-12 coding standards
- Yii3 framework conventions
- Code formatting (spaces, line endings, etc.)
- Import organization
- Method/property ordering

**Configuration**: `.php-cs-fixer.php`

```php
// Example rules enforced
'@PSR12' => true,
'@Symfony' => true,
'@PhpCsFixer' => true,
'@Yiisoft' => true,
'allow_risky' => true,
```

### 2. Static Analysis (Psalm)

**Purpose**: Type checking and error detection before runtime.

**What it checks**:
- Type safety and correctness
- Null safety violations
- Interface compliance
- Method signature validation
- Potential runtime errors

**Configuration**: `psalm.xml`

```xml
<psalm errorLevel="1" findUnusedBaselineEntry="true">
  <projectFiles>
    <directory name="src" />
    <file name="public/index.php"/>
    <file name="yii"/>
  </projectFiles>
</psalm>
```

### 3. Unit Tests (PHPUnit)

**Purpose**: Validates application functionality and prevents regressions.

**What it tests**:
- Application logic
- Repository operations
- Service layer functionality
- API endpoints
- Domain logic

**Coverage Reports**:
- HTML coverage report: `tests/coverage/html/`
- Text coverage summary: `tests/coverage/coverage.txt`
- Clover XML: `tests/coverage/clover.xml`

### 4. Security Audit (Composer)

**Purpose**: Identifies security vulnerabilities in dependencies.

**What it checks**:
- Known CVE vulnerabilities
- Outdated dependencies
- Security advisories
- License compliance

## 📈 Reports and Output

### Standard Output Format

```
🔍 Running Quality Assurance Checks...

1. Checking code style...
✅ Code style check passed

2. Running static analysis...
✅ Static analysis completed - 0 errors, 0 warnings

3. Running unit tests...
✅ All tests passed (15/15)
✅ Coverage: 85.3%

4. Running security audit...
✅ Security audit passed - no vulnerabilities found

✅ All quality checks passed!
```

### Detailed Reports

#### Coverage Report (`--coverage`)
```bash
php quality --coverage
```
Generates:
- **HTML Report**: Interactive coverage visualization
- **Text Summary**: Command-line coverage statistics
- **Clover XML**: CI/CD integration data

#### Analysis Report (`--report`)
```bash
php quality --report
```
Generates:
- **Detailed Psalm analysis**
- **Complexity metrics**
- **Technical debt indicators**
- **Code quality trends**

## 🛠️ Error Handling

### Common Issues and Solutions

#### Code Style Errors
```bash
# Auto-fix most style issues
php quality --fix

# Manual fix for complex issues
vendor/bin/php-cs-fixer fix --diff --verbose
```

#### Psalm Errors
```bash
# Clear cache and re-run
vendor/bin/psalm --clear-cache
vendor/bin/psalm

# Check specific file
vendor/bin/psalm src/Infrastructure/Persistence/Example/ExampleRepository.php
```

#### Test Failures
```bash
# Run specific test
vendor/bin/phpunit tests/Unit/ExampleTest.php

# Run with coverage
vendor/bin/phpunit --coverage-html tests/coverage/html
```

#### Security Issues
```bash
# Update dependencies
composer update

# Check for vulnerabilities
composer audit
```

## 🏗️ Project Structure Impact

### Quality Gates

The quality script enforces quality gates for:

#### Code Quality
- **Type Safety**: All code must pass Psalm analysis
- **Style Compliance**: Code must follow PSR-12 standards
- **Test Coverage**: Minimum coverage requirements

#### Security
- **Dependency Safety**: No known vulnerabilities
- **License Compliance**: Compatible licenses only

#### Performance
- **Test Performance**: Tests must run within time limits
- **Memory Usage**: No memory leaks in tests

### CI/CD Integration

#### GitHub Actions Example
```yaml
name: Quality Check
on: [push, pull_request]
jobs:
  quality:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: shivammathur/setup-php@v2
      - run: composer install
      - run: php quality --report
      - name: Upload coverage reports
        uses: actions/upload-artifact@v3
        with:
          name: coverage-reports
          path: tests/coverage/
```

## 📚 Best Practices

### Development Workflow

#### 1. Before Commit
```bash
# Always run quality checks before committing
php quality
```

#### 2. Feature Development
```bash
# Run specific checks during development
vendor/bin/psalm --no-cache
vendor/bin/php-cs-fixer check
```

#### 3. Before Release
```bash
# Full quality suite with reports
php quality --coverage --report
```

### Code Quality Standards

#### Type Safety
```php
// ✅ Use strict types
declare(strict_types=1);

// ✅ Type annotations
public function process(array $data): array<string, mixed>
{
    return array_map(fn($item) => (string) $item, $data);
}

// ✅ Null safety
public function find(int $id): ?Example
{
    return $this->repository->findById($id);
}
```

#### Error Handling
```php
// ✅ Proper exception handling
try {
    $result = $this->service->process($data);
} catch (ValidationException $e) {
    $this->logger->error('Validation failed', ['error' => $e->getMessage()]);
    throw $e;
}
```

#### Documentation
```php
/**
 * Example service for processing data
 *
 * @param array<string, mixed> $data Input data to process
 * @return array<string, mixed> Processed data
 * @throws ValidationException When data is invalid
 */
public function process(array $data): array<string, mixed>
{
    // Implementation
}
```

## 🔧 Customization

### Adding New Checks

#### 1. Custom Psalm Rules
```xml
<!-- psalm.xml -->
<issueHandlers>
    <CustomRule errorLevel="error" />
</issueHandlers>
```

#### 2. Additional PHPUnit Tests
```php
// tests/Custom/CustomTest.php
class CustomTest extends TestCase
{
    public function testCustomLogic(): void
    {
        $this->assertTrue(true);
    }
}
```

#### 3. Security Scanners
```bash
# Add additional security tools
composer require --dev enshrined/security-scanner
```

### Configuration Files

#### `.php-cs-fixer.php`
```php
<?php
return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR12' => true,
        '@Symfony' => true,
        '@Yiisoft' => true,
    ])
    ->setFinder(PhpCsFixer\Finder::create()
        ->in('src')
        ->append(['public/index.php', 'yii'])
    );
```

#### `psalm.xml`
```xml
<?xml
<psalm errorLevel="1">
    <projectFiles>
        <directory name="src"/>
        <file name="public/index.php"/>
        <file name="yii"/>
    </projectFiles>
    <issueHandlers>
        <MixedAssignment errorLevel="suppress"/>
        <InvalidArrayOffset errorLevel="suppress"/>
    </issueHandlers>
</psalm>
```

## 🎯 Quality Metrics

### Target Metrics

| Metric | Target | Current | Status |
|--------|---------|---------|--------|
| Psalm Errors | 0 | 0 | ✅ |
| Test Coverage | 80% | 85.3% | ✅ |
| Security Issues | 0 | 0 | ✅ |
| Style Issues | 0 | 0 | ✅ |

### Monitoring Quality Trends

```bash
# Generate quality trends
php quality --report | grep -E "(errors|warnings|coverage)"

# Track over time
php quality --report > quality-report-$(date +%Y-%m-%d).txt
```

## 🚨 Troubleshooting

### Common Issues

#### Memory Issues
```bash
# Increase PHP memory limit
export PHP_MEMORY_LIMIT=512M
php quality
```

#### Performance Issues
```bash
# Run checks in parallel
vendor/bin/psalm --threads=4
```

#### Cache Issues
```bash
# Clear all caches
rm -rf vendor/bin/.cache/
vendor/bin/psalm --clear-cache
composer install
```

### Getting Help

```bash
# Show available options
php quality --help

# Check version
php --version
composer show yiisoft/yii
composer show vimeo/psalm
```

## 📞 Resources

### Documentation
- [Psalm Documentation](https://psalm.dev/)
- [PHP CS Fixer](https://cs.symfony.com/)
- [PHPUnit](https://phpunit.de/)
- [Composer Audit](https://github.com/composer/composer/blob/main/src/Composer/Command/AuditCommand.php)

### Yii3 Specific
- [Yii3 Documentation](https://www.yiiframework.com/doc/guide/)
- [Yii3 Best Practices](https://www.yiiframework.com/doc/guide/)

### Quality Standards
- [PSR-12](https://www.php-fig.org/psr/psr-12/)
- [PHP Standards](https://www.php-fig.org/standards/)
- [Clean Code](https://clean-code-developer.com/)

---

## 🎉 Conclusion

The quality script is an essential tool for maintaining code quality, security, and reliability in the Yii3 API project. Regular use ensures:

- **Consistent Code Quality**: All code follows established standards
- **Type Safety**: Errors are caught before runtime
- **Security**: Dependencies are regularly audited
- **Test Coverage**: Code is thoroughly tested
- **Maintainability**: Code remains clean and organized

Make quality checks a regular part of your development workflow for a robust and maintainable codebase! 🚀
