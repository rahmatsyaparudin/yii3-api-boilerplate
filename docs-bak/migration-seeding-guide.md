# Migration and Seeding Guide

## 📋 Overview

This guide covers database migrations and data seeding in the Yii3 API project. It explains how to manage database schema changes, populate initial data, and maintain data consistency across different environments.

---

## 🏗️ Architecture Overview

### Migration Structure

```
src/
├── Migration/                    # Database migrations
│   ├── M20240101000000CreateExample.php     # Table creation
└── Seed/                        # Seed classes (recommended)
    └── M20240101010000SeedExampleData.php   # Modern seed implementation
```

### Seeding Structure

```
src/
├── Console/                     # Console commands
│   └── SeedExampleCommand.php   # Flexible seeding command
├── Seed/                        # Seed classes
│   └── M20240101010000SeedExampleData.php
└── Config/
    └── common/
        └── di/
            └── seed.php       # DI configuration for seeds
```

---

## 🔄 Database Migrations

### Migration Best Practices

#### 1. **Separation of Concerns**
```php
// ✅ GOOD: Separate table creation from data seeding
M20240101000000CreateExample.php     // Table structure only
M20240101010000SeedExampleData.php   // Data seeding only

// ❌ AVOID: Mix structure and data in one migration
M20240101000000CreateExampleWithSeed.php  // Combined approach
```

#### 2. **Naming Convention**
```php
// ✅ Format: M{YYYYMMDDHHMMSS}{Description}
M20240101000000CreateExample.php     // Create table
M20240101010000UpdateExampleFields.php // Update fields
M20240101020000DropExampleIndex.php   // Drop index
```

#### 3. **Reversible Migrations**
```php
// ✅ Always implement RevertibleMigrationInterface
final class M20240101000000CreateExample implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->createTable('example', [...]);
    }
    
    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('example');
    }
}
```

### Creating New Migration

#### 1. **Generate Migration**
```bash
# Create new migration
./yii migrate:create create_user_table

# With custom name
./yii migrate:create M20240101120000CreateUserTable
```

#### 2. **Migration Template**
```php
<?php

declare(strict_types=1);

namespace App\Migration;

// Vendor Layer
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Migration description.
 */
final class M20240101120000CreateUserTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $cb = $b->columnBuilder();

        $b->createTable('user', [
            'id' => $cb::primaryKey(),
            'username' => $cb::string(255)->notNull()->unique(),
            'email' => $cb::string(255)->notNull()->unique(),
            'password_hash' => $cb::string(255)->notNull(),
            'status' => $cb::smallint()->notNull()->defaultValue(1),
            'created_at' => $cb::timestamp()->notNull()->defaultExpression('NOW()'),
            'updated_at' => $cb::timestamp()->notNull()->defaultExpression('NOW()'),
        ]);

        // Create indexes
        $b->createIndex('idx_user_status', 'user', 'status');
        $b->createIndex('idx_user_created_at', 'user', 'created_at');
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('user');
    }
}
```

### Running Migrations

#### 1. **Basic Migration Commands**
```bash
# Run all pending migrations
./yii migrate:up

# Run specific migration
./yii migrate:up M20240101000000CreateExample

# Run multiple migrations
./yii migrate:up M20240101000000CreateExample
```

#### 2. **Migration Management**
```bash
# Show migration history
./yii migrate:history

# Show pending migrations
./yii migrate:new

# Mark migration as applied (without running)
./yii migrate:mark M20240101000000CreateExample

# Rollback last migration
./yii migrate:down

# Rollback to specific migration
./yii migrate:down M20240101000000CreateExample
```

#### 3. **Environment-Specific Migrations**
```bash
# Development environment
APP_ENV=dev ./yii migrate:up

# Production environment
APP_ENV=prod ./yii migrate:up

# Test environment
APP_ENV=test ./yii migrate:up
```

---

## 🌱 Data Seeding

### Seeding Approaches

#### 1. **Console Command (Recommended)**
```php
// ✅ Flexible and reusable
final class SeedExampleCommand extends Command
{
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        // Environment check
        if (!in_array($_ENV['APP_ENV'] ?? '', ['dev', 'development'], true)) {
            $output->writeln('<error>Seed command can only be run in development environment.</error>');
            return Command::FAILURE;
        }
        
        // Flexible seeding logic
        $count = (int) $input->getOption('count');
        $truncate = $input->getOption('truncate');
        
        // ... seeding implementation
    }
}
```

#### 2. **Seed Classes (Modern Approach)**
```php
// ✅ Modern, reusable seed classes
namespace App\Seed;

final class M20240101010000SeedExampleData
{
    public function __construct(
        private ClockInterface $clock,
        private ConnectionInterface $db
    ) {}
    
    public function up(): void
    {
        // Environment check
        if (!in_array($_ENV['APP_ENV'] ?? '', ['dev', 'development'], true)) {
            echo "Seed skipped: Development environment only.\n";
            return;
        }
        
        // Seeding logic
    }
    
    public function down(): void
    {
        // Cleanup logic
    }
}
```

#### 3. **Migration-Based Seeding (Legacy)**
```php
// ❌ Not recommended - mixed concerns
final class M20240101010000SeedExampleData implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        // Environment check
        if (!in_array($_ENV['APP_ENV'] ?? '', ['dev', 'development'], true)) {
            echo "Seed migration skipped.\n";
            return;
        }
        
        // Direct database operations
        $b->insert('example', [...]);
    }
}
```

### Console Seeding Commands

#### 1. **Basic Seeding**
```bash
# Seed with default options (5 records)
./yii seed:example

# Seed with custom count
./yii seed:example --count=20

# Truncate and reseed
./yii seed:example --truncate

# Custom count with truncate
./yii seed:example --count=10 --truncate
```

#### 2. **Command Options**
```bash
# Show help
./yii help seed:example

# Available options:
#   --truncate, -t    Truncate table before seeding
#   --count, -c      Number of records to seed (default: 5)
```

#### 3. **Command Implementation**
```php
final class SeedExampleCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('seed:example')
            ->setDescription('Seed example table with initial data')
            ->addOption('truncate', 't', InputOption::VALUE_NONE, 'Truncate table before seeding')
            ->addOption('count', 'c', InputOption::VALUE_OPTIONAL, 'Number of records to seed', '5');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Environment validation
        if (!in_array($_ENV['APP_ENV'] ?? '', ['dev', 'development'], true)) {
            $output->writeln('<error>Seed command can only be run in development environment.</error>');
            return Command::FAILURE;
        }
        
        $truncate = $input->getOption('truncate');
        $count = (int) $input->getOption('count');
        
        // Implementation
        return Command::SUCCESS;
    }
}
```

### Seed Data Management

#### 1. **Data Generation**
```php
private function generateExampleData(int $count): array
{
    $defaultExamples = [
        ['name' => 'Asus', 'status' => RecordStatus::DRAFT->value],
        ['name' => 'Acer', 'status' => RecordStatus::DRAFT->value],
        ['name' => 'Intel', 'status' => RecordStatus::DRAFT->value],
        ['name' => 'AMD', 'status' => RecordStatus::DRAFT->value],
        ['name' => 'Klevv', 'status' => RecordStatus::DRAFT->value],
    ];
    
    if ($count <= count($defaultExamples)) {
        return array_slice($defaultExamples, 0, $count);
    }
    
    // Generate additional data if needed
    $additionalExamples = [];
    for ($i = count($defaultExamples); $i < $count; $i++) {
        $additionalExamples[] = [
            'name' => 'Example ' . ($i + 1),
            'status' => RecordStatus::DRAFT->value,
        ];
    }
    
    return array_merge($defaultExamples, $additionalExamples);
}
```

#### 2. **Data Insertion**
```php
foreach ($examples as $data) {
    $this->db->createCommand()->insert('example', [
        'name' => $data['name'],
        'status' => $data['status'],
        'detail_info' => [
            'change_log' => [
                'created_at' => $createdAt,
                'created_by' => $user,
                'deleted_at' => null,
                'deleted_by' => null,
                'updated_at' => null,
                'updated_by' => null,
            ],
        ],
        'sync_mdb' => null,
        'lock_version' => 1,
    ])->execute();
}
```

#### 3. **Data Cleanup**
```php
public function down(): void
{
    // Remove seeded data
    $this->db->createCommand()
        ->delete('example', ['name' => ['Asus', 'Acer', 'Intel', 'AMD', 'Klevv']])
        ->execute();
}
```

---

## 🔧 Configuration

### Migration Configuration

#### 1. **Migration Settings**
```php
// config/common/params.php
return [
    'yiisoft/db-migration' => [
        'historyTable' => 'migration',
        'migrationPath' => '@src/Migration',
    ],
];
```

#### 2. **Database Configuration**
```php
// config/common/di/db-pgsql.php
return [
    ConnectionInterface::class => [
        'class' => Connection::class,
        '__construct()' => [
            'driver' => new Driver(
                $params['yiisoft/db-pgsql']['dsn'],
                $params['yiisoft/db-pgsql']['username'],
                $params['yiisoft/db-pgsql']['password'],
            ),
        ],
    ],
];
```

### Seeding Configuration

#### 1. **DI Configuration**
```php
// config/common/di/seed.php
return [
    SeedExampleCommand::class => [
        'class' => SeedExampleCommand::class,
        '__construct()' => [
            Reference::to(ClockInterface::class),
            Reference::to(ConnectionInterface::class),
        ],
    ],
];
```

#### 2. **Console Commands**
```php
// config/console/commands.php
return [
    'hello' => Console\HelloCommand::class,
    'seed:example' => Console\SeedExampleCommand::class,
];
```

#### 3. **Console Parameters**
```php
// config/console/params.php
return [
    'yiisoft/yii-console' => [
        'commands' => require __DIR__ . '/commands.php',
    ],
];
```

---

## 🚀 Development Workflow

### 1. **Project Setup**
```bash
# 1. Install dependencies
composer install

# 2. Create database
createdb yii3_api

# 3. Run migrations
./yii migrate:up

# 4. Seed data (development only)
./yii seed:example

# 5. Start development server
./yii serve
```

### 2. **Development Cycle**
```bash
# During development
./yii migrate:up                    # Apply new migrations
./yii seed:example --truncate        # Refresh seed data
./yii serve                         # Start server
```

### 3. **Testing Workflow**
```bash
# Test environment
APP_ENV=test ./yii migrate:up        # Create test schema
./yii seed:example --count=100        # Seed test data
./yii test                           # Run tests
```

### 4. **Production Deployment**
```bash
# Production environment
APP_ENV=prod ./yii migrate:up         # Apply migrations only
# No seeding in production
```

---

## 🛡️ Environment Safety

### Environment Restrictions

#### 1. **Development Only Seeding**
```php
// ✅ Environment check in commands
if (!in_array($_ENV['APP_ENV'] ?? '', ['dev', 'development'], true)) {
    $output->writeln('<error>Seed command can only be run in development environment.</error>');
    return Command::FAILURE;
}
```

#### 2. **Environment Variables**
```bash
# .env.example
APP_ENV=dev
APP_DEBUG=1

# .env.production
APP_ENV=prod
APP_DEBUG=0
```

#### 3. **Environment Detection**
```php
// ✅ Multiple development environment support
$devEnvironments = ['dev', 'development', 'local'];
if (!in_array($_ENV['APP_ENV'] ?? '', $devEnvironments, true)) {
    // Block seeding
}
```

### Safety Measures

#### 1. **Confirmation Prompts**
```php
// ✅ Add confirmation for destructive operations
if ($truncate) {
    $helper = $this->getHelper('question');
    $question = new ConfirmationQuestion(
        'This will truncate the example table. Continue? (y/n)',
        false
    );
    
    if (!$helper->ask($input, $output, $question)) {
        return Command::SUCCESS;
    }
}
```

#### 2. **Backup Before Seeding**
```php
// ✅ Create backup before destructive operations
if ($truncate) {
    $backupTable = 'example_backup_' . date('Y_m_d_His');
    $this->db->createCommand()->createTable($backupTable, $this->db->getTableSchema('example'))->execute();
    
    // After seeding, you can restore if needed
    // $this->db->createCommand()->dropTable('example')->execute();
    // $this->db->createCommand()->renameTable($backupTable, 'example')->execute();
}
```

---

## 📊 Best Practices

### Migration Best Practices

#### 1. **Single Responsibility**
```php
// ✅ One migration, one purpose
M20240101000000CreateExample.php     // Create table
M20240101010000AddExampleFields.php  // Add fields
M20240101020000CreateExampleIndex.php // Create index

// ❌ Avoid: Multiple operations in one migration
M20240101000000CreateExampleWithFieldsAndIndex.php
```

#### 2. **Idempotent Migrations**
```php
// ✅ Check before creating
if (!$this->tableExists('example')) {
    $b->createTable('example', [...]);
}

// ✅ Check before adding column
if (!$this->columnExists('example', 'new_field')) {
    $b->addColumn('example', 'new_field', $type);
}
```

#### 3. **Rollback Safety**
```php
// ✅ Always implement down() method
public function down(MigrationBuilder $b): void
{
    // Reverse all operations from up()
    $b->dropTable('example');
}
```

### Seeding Best Practices

#### 1. **Environment Awareness**
```php
// ✅ Always check environment
if (!in_array($_ENV['APP_ENV'] ?? '', ['dev', 'development'], true)) {
    throw new \RuntimeException('Seeding only allowed in development');
}
```

#### 2. **Data Consistency**
```php
// ✅ Use transactions for data consistency
$this->db->transaction(function() use ($data) {
    foreach ($data as $item) {
        $this->db->createCommand()->insert('example', $item)->execute();
    }
});
```

#### 3. **Error Handling**
```php
// ✅ Handle errors gracefully
try {
    $this->db->createCommand()->insert('example', $data)->execute();
} catch (\Exception $e) {
    $output->writeln('<error>Failed to seed data: ' . $e->getMessage() . '</error>');
    return Command::FAILURE;
}
```

---

## 🔍 Troubleshooting

### Common Migration Issues

#### 1. **Migration Conflicts**
```bash
# Error: Migration already applied
./yii migrate:mark M20240101000000CreateExample

# Error: Migration not found
./yii migrate:history  # Check applied migrations
```

#### 2. **Database Connection Issues**
```bash
# Check database connection
./yii db/info

# Test database connection
./yii db/test
```

#### 3. **Migration Rollback Issues**
```bash
# Force rollback (use with caution)
./yii migrate:down --force

# Check migration dependencies
./yii migrate:history
```

### Common Seeding Issues

#### 1. **Environment Errors**
```bash
# Check current environment
echo $APP_ENV

# Set environment manually
export APP_ENV=dev
./yii seed:example
```

#### 2. **Permission Issues**
```bash
# Check database permissions
./yii db/info

# Test write permissions
./yii seed:example --count=1
```

#### 3. **Data Conflicts**
```bash
# Clear existing data
./yii seed:example --truncate

# Check existing data
./yii db/query "SELECT COUNT(*) FROM example"
```

---

## 📚 Advanced Topics

### 1. **Conditional Migrations**
```php
public function up(MigrationBuilder $b): void
{
    // Only run in specific conditions
    if ($this->isProductionEnvironment()) {
        return; // Skip in production
    }
    
    // Migration logic
}
```

### 2. **Data Transformations**
```php
public function up(MigrationBuilder $b): void
{
    // Transform existing data
    $b->update('example', [
        'status' => RecordStatus::ACTIVE->value
    ], ['status' => RecordStatus::DRAFT->value]);
}
```

### 3. **Batch Operations**
```php
public function up(MigrationBuilder $b): void
{
    // Process in batches for large datasets
    $batchSize = 1000;
    $offset = 0;
    
    do {
        $data = $this->generateBatch($offset, $batchSize);
        
        foreach ($data as $item) {
            $b->insert('example', $item);
        }
        
        $offset += $batchSize;
    } while (count($data) === $batchSize);
}
```

---

## 🎯 Summary

### Key Takeaways

1. **Separate Concerns**: Keep migrations for schema, seeds for data
2. **Environment Safety**: Restrict seeding to development only
3. **Reversible Operations**: Always implement rollback logic
4. **Flexible Seeding**: Use console commands for reusable seeding
5. **Error Handling**: Implement proper error handling and logging

### Recommended Workflow

```bash
# Development
./yii migrate:up                    # Apply schema changes
./yii seed:example --truncate        # Refresh seed data
./yii serve                         # Start development

# Testing
APP_ENV=test ./yii migrate:up        # Test schema
./yii seed:example --count=100        # Test data
./yii test                           # Run tests

# Production
APP_ENV=prod ./yii migrate:up         # Production schema only
# No seeding in production
```

---

## 📞 Resources

### Documentation
- **[Yii3 Migration Guide](https://www.yiiframework.com/doc/guide/2.0/en/db-migrations.html)**: Official migration documentation
- **[Yii3 Console Guide](https://www.yiiframework.com/doc/guide/2.0/en/tutorial-console.html)**: Console application guide
- **[Database Best Practices](https://www.yiiframework.com/doc/guide/2.0/en/db-dao.html)**: Database access patterns

### Tools
- **[Yii3 Migration Tool](https://www.yiiframework.com/doc/api/2.0/class-yii-db-migration-migration.html)**: Migration API reference
- **[Yii3 Console](https://www.yiiframework.com/doc/api/2.0/class-yii-console-controller.html)**: Console controller reference

### Examples
- **[Project Examples](../src/Migration/)**: Migration examples in this project
- **[Seed Examples](../src/Seed/)**: Seed examples in this project
- **[Console Examples](../src/Console/)**: Console command examples

---

## 🎉 Conclusion

Proper migration and seeding management is crucial for maintaining data consistency and supporting development workflows. By following the patterns and best practices outlined in this guide, you can ensure reliable database schema management and safe data seeding across different environments.

Key benefits of this approach:

- **🏗️ Clean Architecture**: Separated concerns for migrations and seeding
- **🔒 Environment Safety**: Development-only seeding with proper checks
- **🔄 Reversible Operations**: Full rollback support for all changes
- **🚀 Flexible Seeding**: Reusable console commands with options
- **📊 Consistent Data**: Reliable data population across environments

Implement these practices in your Yii3 API project for robust database management! 🚀
