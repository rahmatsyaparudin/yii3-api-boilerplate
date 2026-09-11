# Quality Assurance Guide

## 📋 Overview

The `quality` script (a small Symfony Console app at the project root) is a comprehensive quality assurance tool designed for the Yii3 API project. It provides automated checks for code style, static analysis, testing, and security auditing to ensure high code quality and maintainability.

## 🚀 Quick Start

### Running All Quality Checks
```bash
# Run complete quality check suite
php quality quality:check

# Run with specific options
php quality quality:check --fix          # Auto-fix code style issues
php quality quality:check --coverage      # Generate test coverage reports
php quality quality:check --report        # Generate detailed analysis reports
```

### Running Tests
```bash
# Run all Codeception suites
php quality test:run

# Run specific test types
php quality test:run --unit                    # Run only the Unit suite
php quality test:run --integration             # Run the Functional suite
php quality test:run --coverage                # Generate coverage report
php quality test:run --filter=testSomething    # Filter tests by name
```

### Available Commands
```bash
# List all available commands
php quality list

# Get help for specific command
php quality quality:check --help
php quality test:run --help
```

### Individual Checks
```bash
# Code style check
vendor/bin/php-cs-fixer check --diff --verbose --allow-risky=yes

# Static analysis
vendor/bin/psalm

# Tests (Codeception; Unit suite is what quality:check runs)
vendor/bin/codecept run Unit

# Security audit
composer audit
```

On Windows, prefix the `vendor/bin/*` commands with `php` (e.g. `php vendor\bin\psalm`).

## 🔧 Configuration

### Available Options

`quality:check` options:

| Option | Short | Description |
|--------|-------|-------------|
| `--fix` | `-f` | Auto-fix code style issues |
| `--coverage` | `-c` | Generate test coverage reports |
| `--report` | `-r` | Generate detailed analysis reports |

`test:run` options:

| Option | Short | Description |
|--------|-------|-------------|
| `--unit` | `-u` | Run only the Unit suite |
| `--integration` | `-i` | Run only the Functional suite |
| `--coverage` | `-c` | Generate coverage report |
| `--filter` | — | Filter tests by name |

### What `quality:check` Runs

1. `vendor/bin/php-cs-fixer check|fix --diff --verbose --allow-risky=yes`
2. `vendor/bin/psalm`
3. `vendor/bin/codecept run Unit` (with `APP_ENV=test`; adds `--coverage-html`/`--coverage-text` with `--coverage`, plus `--coverage-xml` with `--report`)
4. `composer audit`

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
// Rule sets enabled in .php-cs-fixer.php
'@PER-CS2x0' => true,
'@PSR12' => true,
'@Symfony' => true,
'@PHP8x0Migration' => true,
'@PHP8x0Migration:risky' => true,
'@PHP8x1Migration' => true,
'strict_comparison' => true,
'declare_strict_types' => true,
'no_unused_imports' => true,
// ...see .php-cs-fixer.php for the full list
```

The finder scans `config/`, `src/`, `tests/` plus `public/index.php`; the cache file is `runtime/cache/.php-cs-fixer.cache`.

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
<psalm
  errorLevel="1"
  errorBaseline="psalm-baseline.xml"
  findUnusedBaselineEntry="true"
  findUnusedCode="false"
  cacheDirectory="/app/runtime/cache/psalm"
>
  <projectFiles>
    <directory name="src" />
    <file name="public/index.php"/>
    <file name="yii"/>
  </projectFiles>
</psalm>
```

A baseline of known issues is kept in `psalm-baseline.xml`; a number of mixed- type issues are suppressed via `<issueHandlers>` in `psalm.xml`.

### 3. Unit Tests (Codeception)

**Purpose**: Validates application functionality and prevents regressions.

`quality:check` runs `vendor/bin/codecept run Unit` with `APP_ENV=test`. The full test setup is defined in `codeception.yml` (namespace `App\Tests`) with suites under `tests/`: `Unit`, `Functional`, `Api`, `Console`.

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

### Additional QA Tools

Not part of `quality:check`, but configured in the project (also available as `make` targets when using Docker):

```bash
# Rector — automated refactorings (rector.php: src/ + tests/, PHP 8.2 sets)
vendor/bin/rector --dry-run  # preview changes
vendor/bin/rector            # apply changes

# Infection — mutation testing (infection.json.dist)
vendor/bin/infection

# Composer Dependency Analyser (composer-dependency-analyser.php)
vendor/bin/composer-dependency-analyser --config=composer-dependency-analyser.php
```

## 📈 Reports and Output

### Standard Output Format

```
Running Quality Assurance Checks...

1. Checking code style...
Running: php vendor\bin\php-cs-fixer check --diff --verbose --allow-risky=yes
...

2. Running static analysis...
Running: php vendor\bin\psalm
...

3. Running unit tests...
Running: php vendor\bin\codecept run Unit
...

4. Running security audit...
Running: composer audit
...

✅ All quality checks passed!
```

(Or `❌ Some quality checks failed!` and a non-zero exit code when a step fails.)

### Detailed Reports

#### Coverage Report (`--coverage`)
```bash
php quality quality:check --coverage
```
Generates:
- **HTML Report**: `tests/coverage/html/` — interactive coverage visualization
- **Text Summary**: `tests/coverage/coverage.txt` — coverage statistics

#### Analysis Report (`--report`)
```bash
php quality quality:check --report
```
Generates:
- **Clover XML**: `tests/coverage/clover.xml` — CI/CD integration data

## 🛠️ Error Handling

### Common Issues and Solutions

#### Code Style Errors
```bash
# Auto-fix most style issues
php quality quality:check --fix

# Manual fix for complex issues
vendor/bin/php-cs-fixer fix --diff --verbose --allow-risky=yes
```

#### Psalm Errors
```bash
# Clear cache and re-run
vendor/bin/psalm --clear-cache
vendor/bin/psalm

# Check specific file
vendor/bin/psalm src/Infrastructure/Common/Persistence/Example/ExampleRepository.php
```

#### Test Failures
```bash
# Run a specific suite or test
vendor/bin/codecept run Unit
vendor/bin/codecept run Unit --filter=ExampleTest

# Run with coverage
vendor/bin/codecept run --coverage-html
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
      - run: php quality quality:check --report
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
php quality quality:check
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
php quality quality:check --coverage --report
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

#### 2. Additional Codeception Tests
```php
// tests/Unit/CustomTest.php
final class CustomTest extends \Codeception\Test\Unit
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
$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__ . '/config', __DIR__ . '/src', __DIR__ . '/tests'])
    ->append([__DIR__ . '/public/index.php']);

return (new PhpCsFixer\Config())
    ->setCacheFile(__DIR__ . '/runtime/cache/.php-cs-fixer.cache')
    ->setRules([
        '@PER-CS2x0' => true,
        '@PSR12' => true,
        '@Symfony' => true,
    ])
    ->setFinder($finder);
```

#### `psalm.xml`
```xml
<?xml version="1.0"?>
<psalm errorLevel="1" errorBaseline="psalm-baseline.xml" findUnusedBaselineEntry="true">
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

| Metric | Target | How to check |
|--------|---------|--------|
| Psalm Errors | 0 | `vendor/bin/psalm` |
| Test Coverage | as high as practical | `php quality quality:check --coverage` |
| Security Issues | 0 | `composer audit` |
| Style Issues | 0 | `vendor/bin/php-cs-fixer check` |

### Monitoring Quality Trends

```bash
# Track over time
php quality quality:check --report > quality-report-$(date +%Y-%m-%d).txt
```

## 🚨 Troubleshooting

### Common Issues

#### Memory Issues
```bash
# Increase PHP memory limit
php -d memory_limit=1G quality quality:check
```

#### Performance Issues
```bash
# Run checks in parallel
vendor/bin/psalm --threads=4
```

#### Cache Issues
```bash
# Clear tool caches
rm -rf runtime/cache/*
vendor/bin/psalm --clear-cache
composer install
```

### Getting Help

```bash
# Show available commands and options
php quality list
php quality quality:check --help

# Check versions
php --version
composer show yiisoft/yii-console
composer show vimeo/psalm
```

## 📞 Resources

### Documentation
- [Psalm Documentation](https://psalm.dev/)
- [PHP CS Fixer](https://cs.symfony.com/)
- [Codeception](https://codeception.com/) (test runner; PHPUnit underneath)
- [Rector](https://github.com/rectorphp/rector), [Infection](https://infection.github.io/), [Composer Dependency Analyser](https://github.com/shipmonk-rnd/composer-dependency-analyser)
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
