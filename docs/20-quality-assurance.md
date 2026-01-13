# Quality Assurance

## Overview

Quality assurance implementation using Yii3 OOP style with automated testing, static analysis, and code style enforcement.

## Current Status

### Working Components
- **CustomMonitoringService** - 11/11 tests passing
- **RateLimitMiddleware** - Basic functionality working
- **Code Style** - PHP CS Fixer working with updated rules
- **Static Analysis** - Psalm configured and running
- **Security Audit** - Composer audit working

### Issues Being Addressed
- **Brand Domain Tests** - Missing Yiisoft\Db\ActiveRecord dependency
- **Legacy Tests** - Codeception integration issues (old test framework)
- **Mock Issues** - Some PSR-7 interface mock problems

### Test Results Summary
```
CustomMonitoringService: 11/11 
RateLimitMiddleware: 1/3 
Brand Domain: 0/9 (dependency issue)
Legacy Tests: 0/20 (framework issues)
```

## Setup

### Required Packages

```bash
# Development dependencies
composer require --dev phpunit/phpunit friendsofphp/php-cs-fixer vimeo/psalm

# Additional QA tools
composer require --dev symfony/console enlightn/security-checker
```

### Configuration Files

- **phpunit.xml** - PHPUnit configuration with test suites and coverage
- **psalm.xml** - Static analysis configuration
- **.php-cs-fixer.php** - Code style configuration
- **quality** - CLI tool for running all checks

## Testing Framework

### Test Structure

```
tests/
├── TestCase.php              # Base test case class
├── Unit/                     # Unit tests
│   ├── Domain/
│   │   └── Brand/
│   │       └── BrandTest.php
│   └── Infrastructure/
│       └── Monitoring/
│           └── CustomMonitoringServiceTest.php
├── Integration/              # Integration tests
│   └── Api/
│       └── V1/
│           └── BrandApiTest.php
└── Feature/                  # Feature tests (end-to-end)
```

### Base Test Case

```php
abstract class Yii3TestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_ENV['APP_ENV'] = 'testing';
        $_ENV['DB_DSN'] = 'sqlite::memory:';
    }

    protected function assertApiResponseStructure(array $response): void
    {
        $this->assertArrayHasKeys(['success', 'data', 'message'], $response);
        $this->assertIsBool($response['success']);
        $this->assertIsArray($response['data']);
        $this->assertIsString($response['message']);
    }
}
```

### Unit Tests

```php
final class CustomMonitoringServiceTest extends TestCase
{
    public function testLogRequest(): void
    {
        $this->monitoringService->logRequest(['action' => 'test']);
        
        $logs = $this->monitoringService->getLogs();
        $this->assertCount(1, $logs);
        $this->assertEquals('INFO', $logs[0]['level']);
    }
}
```

### Integration Tests

```php
final class BrandApiTest extends TestCase
{
    public function testGetBrandsReturnsSuccessResponse(): void
    {
        $response = $this->apiClient->get('/v1/brand');
        
        $this->assertApiResponseStructure($response);
        $this->assertIsArray($response['data']);
    }
}
```

## Static Analysis

### Psalm Configuration

```xml
<psalm errorLevel="1" resolveFromConfigFile="true">
    <projectFiles>
        <directory name="src" />
        <ignoreFiles>
            <directory name="vendor" />
            <directory name="runtime" />
        </ignoreFiles>
    </projectFiles>
    
    <issueHandlers>
        <LessSpecificReturnType errorLevel="info" />
        <MissingReturnType errorLevel="info" />
        <InvalidArgument>
            <errorLevel type="suppress">
                <referencedFunction>PHPUnit\Framework\Assert::*</referencedFunction>
            </errorLevel>
        </InvalidArgument>
    </issueHandlers>
</psalm>
```

### Running Static Analysis

```bash
# Basic analysis
vendor/bin/psalm

# With progress
vendor/bin/psalm --show-progress=none

# Generate baseline
vendor/bin/psalm --set-baseline=psalm-baseline.xml
```

## Code Style

### PHP CS Fixer Configuration

```php
return (new Config())
    ->setRules([
        '@PER-CS2x0' => true,
        '@PSR12' => true,
        '@Symfony' => true,
        '@PHP8x0Migration' => true,
        '@PHP8x0Migration:risky' => true,
        '@PHP8x1Migration' => true,
        'strict_comparison' => true,
        'array_syntax' => ['syntax' => 'short'],
        'declare_strict_types' => true,
        'no_unused_imports' => true,
        // ... more rules
    ]);
```

### Running Code Style Checks

```bash
# Check style
vendor/bin/php-cs-fixer check --diff --verbose

# Fix style issues
vendor/bin/php-cs-fixer fix --diff --verbose

# Check specific directory
vendor/bin/php-cs-fixer check src/Infrastructure/Monitoring
```

## Quality CLI Tool

### Usage

```bash
# Run all quality checks
./quality quality:check

# Fix code style issues
./quality quality:check --fix

# Generate coverage reports
./quality quality:check --coverage

# Run specific test suites
./quality test:run --unit
./quality test:run --integration
./quality test:run --filter "BrandTest"

# Run tests with coverage
./quality test:run --coverage
```

### CLI Commands

#### Quality Check Command
- Runs code style checks
- Runs static analysis
- Runs unit tests
- Runs security audit
- Generates coverage reports (optional)

#### Test Command
- Run specific test suites (unit/integration)
- Filter tests by name
- Generate coverage reports

## Continuous Integration

### GitHub Actions Workflow

```yaml
name: Quality Assurance

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php-version: ['8.1', '8.2', '8.3']
    
    steps:
    - uses: actions/checkout@v4
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ matrix.php-version }}
        coverage: xdebug
        
    - name: Install dependencies
      run: composer install --no-progress --no-interaction
      
    - name: Check code style
      run: vendor/bin/php-cs-fixer check --diff --verbose
      
    - name: Run static analysis
      run: vendor/bin/psalm --show-progress=none
      
    - name: Run tests
      run: vendor/bin/phpunit --coverage-text --coverage-clover=coverage.xml
```

## Best Practices

### 1. Test Organization
- **Unit Tests**: Test individual classes in isolation
- **Integration Tests**: Test component interactions
- **Feature Tests**: Test complete workflows

### 2. Test Naming
- Use descriptive test method names
- Follow `test[Feature]ExpectedBehavior` pattern
- Use `@test` annotation for readability

### 3. Assertions
- Use specific assertions for better error messages
- Test both positive and negative cases
- Include edge cases and boundary conditions

### 4. Test Data
- Use factories for test data creation
- Keep test data minimal and focused
- Clean up after tests

### 5. Static Analysis
- Address all Psalm issues
- Use type hints consistently
- Document complex logic with PHPDoc

### 6. Code Style
- Follow PSR-12 standards
- Use strict types everywhere
- Keep code readable and maintainable

## Coverage Goals

- **Unit Tests**: 90%+ line coverage
- **Integration Tests**: 80%+ line coverage
- **Critical Paths**: 100% coverage

## Security

### Security Audit

```bash
# Check for security vulnerabilities
composer audit

# Using Enlightn Security Checker
~/.composer/vendor/bin/security-checker security:check
```

### Security Best Practices
- Validate all inputs
- Sanitize outputs
- Use parameterized queries
- Implement proper authentication/authorization
- Keep dependencies updated

## Performance

### Test Performance
- Use in-memory databases for tests
- Mock external dependencies
- Run tests in parallel when possible

### Static Analysis Performance
- Use Psalm cache
- Exclude vendor directories
- Run incremental analysis

## Documentation

### Test Documentation
- Document complex test scenarios
- Explain business logic in tests
- Keep test documentation up to date

### Code Documentation
- Use PHPDoc for all public methods
- Document complex algorithms
- Include usage examples

## Troubleshooting

### Common Issues

1. **PHPUnit Failures**
   - Check test environment setup
   - Verify database migrations
   - Clear test cache

2. **Psalm Issues**
   - Update Psalm baseline
   - Check type annotations
   - Review suppressed issues

3. **Code Style Issues**
   - Run with `--fix` option
   - Check for conflicting rules
   - Review configuration

### Debug Mode

```bash
# Run tests with debug output
./quality test:run --debug

# Run Psalm with verbose output
vendor/bin/psalm --verbose

# Check code style with details
vendor/bin/php-cs-fixer check --diff --verbose
```
