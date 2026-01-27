# Audit Log Strategy

## Overview

This document outlines the audit logging strategy for tracking create, update, and delete operations in the Yii3 API application.

## Architecture

### 1. Audit Table Structure

```sql
CREATE TABLE audit_logs (
    id BIGSERIAL PRIMARY KEY,
    table_name VARCHAR(255) NOT NULL,
    record_id BIGINT NOT NULL,
    action VARCHAR(20) NOT NULL CHECK (action IN ('INSERT', 'UPDATE', 'DELETE')),
    old_values JSONB,
    new_values JSONB,
    user_id INTEGER,
    user_name VARCHAR(255),
    ip_address INET,
    user_agent TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    INDEX idx_audit_table_record (table_name, record_id),
    INDEX idx_audit_user (user_id),
    INDEX idx_audit_created (created_at)
);
```

### 2. Audit Trait

Create a trait for auditable models:

```php
<?php
// src/Domain/Common/Audit/AuditableTrait.php

declare(strict_types=1);

namespace App\Domain\Common\Audit;

use App\Domain\Common\Audit\Actor;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;

trait AuditableTrait
{
    protected array $oldAttributes = [];
    protected bool $auditEnabled = true;

    public function enableAudit(): void
    {
        $this->auditEnabled = true;
    }

    public function disableAudit(): void
    {
        $this->auditEnabled = false;
    }

    public function beforeSave(): bool
    {
        if ($this->auditEnabled && $this->getIsNewRecord() === false) {
            $this->oldAttributes = $this->getOldAttributes();
        }
        return parent::beforeSave();
    }

    public function afterSave(): void
    {
        if ($this->auditEnabled) {
            $this->logAudit();
        }
        parent::afterSave();
    }

    public function afterDelete(): void
    {
        if ($this->auditEnabled) {
            $this->logAudit('DELETE');
        }
        parent::afterDelete();
    }

    protected function logAudit(string $action = null): void
    {
        $action ??= $this->getIsNewRecord() ? 'INSERT' : 'UPDATE';
        $tableName = $this->getTableName();
        $recordId = $this->getPrimaryKey();

        $oldValues = null;
        $newValues = null;

        if ($action === 'UPDATE') {
            $oldValues = $this->oldAttributes;
            $newValues = $this->getAttributes();
        } elseif ($action === 'INSERT') {
            $newValues = $this->getAttributes();
        } elseif ($action === 'DELETE') {
            $oldValues = $this->oldAttributes;
        }

        $this->insertAuditLog($tableName, $recordId, $action, $oldValues, $newValues);
    }

    protected function insertAuditLog(
        string $tableName,
        int $recordId,
        string $action,
        ?array $oldValues,
        ?array $newValues
    ): void {
        $actor = $this->getCurrentActor();

        (new Query())->createCommand()->insert('audit_logs', [
            'table_name' => $tableName,
            'record_id' => $recordId,
            'action' => $action,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'user_id' => $actor?->id,
            'user_name' => $actor?->username,
            'ip_address' => $this->getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => new Expression('NOW()'),
        ])->execute();
    }

    protected function getCurrentActor(): ?Actor
    {
        // Get current actor from your auth context
        // This depends on your implementation
        return null; // Implement based on your auth system
    }

    protected function getClientIp(): ?string
    {
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                return trim($ips[0]);
            }
        }

        return null;
    }
}
```

### 3. Example Model Usage

```php
<?php
// src/Domain/Brand/Brand.php

declare(strict_types=1);

namespace App\Domain\Brand;

use App\Domain\Common\Audit\AuditableTrait;
use Yiisoft\Db\ActiveRecord;

class Brand extends ActiveRecord
{
    use AuditableTrait;

    public static function tableName(): string
    {
        return 'brands';
    }

    public function rules(): array
    {
        return [
            [['name', 'code'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['code'], 'string', 'max' => 50],
            [['is_active'], 'boolean'],
        ];
    }
}
```

## Configuration

### 1. Database Migration

Create migration for audit table:

```php
<?php
// migrations/m20240101_000000_create_audit_logs.php

declare(strict_types=1);

use Yiisoft\Db\Migration\Migration;
use Yiisoft\Db\Connection;

final class m20240101_000000_create_audit_logs extends Migration
{
    public function up(Connection $db): void
    {
        $this->createTable('audit_logs', [
            'id' => $this->bigPrimaryKey(),
            'table_name' => $this->string(255)->notNull(),
            'record_id' => $this->bigInteger()->notNull(),
            'action' => $this->string(20)->notNull(),
            'old_values' => $this->json(),
            'new_values' => $this->json(),
            'user_id' => $this->integer(),
            'user_name' => $this->string(255),
            'ip_address' => $this->string(45),
            'user_agent' => $this->text(),
            'created_at' => $this->timestamp()->defaultExpression('NOW()'),
        ]);

        $this->createIndex('idx_audit_table_record', 'audit_logs', ['table_name', 'record_id']);
        $this->createIndex('idx_audit_user', 'audit_logs', ['user_id']);
        $this->createIndex('idx_audit_created', 'audit_logs', ['created_at']);

        $this->addCheck(
            'chk_audit_action',
            'audit_logs',
            "action IN ('INSERT', 'UPDATE', 'DELETE')"
        );
    }

    public function down(Connection $db): void
    {
        $this->dropTable('audit_logs');
    }
}
```

### 2. Service Configuration

Register audit service in DI:

```php
<?php
// config/common/di/audit.php

declare(strict_types=1);

use App\Domain\Common\Audit\AuditService;
use Yiisoft\Db\Connection;

return [
    AuditService::class => static function (Connection $db) {
        return new AuditService($db);
    },
];
```

## Audit Service Implementation

```php
<?php
// src/Domain/Common/Audit/AuditService.php

declare(strict_types=1);

use App\Domain\Common\Audit\Actor;
use Yiisoft\Db\Connection;
use Yiisoft\Db\Query\Query;

class AuditService
{
    public function __construct(private Connection $db) {}

    public function log(
        string $tableName,
        int $recordId,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?Actor $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        (new Query())->createCommand($this->db)->insert('audit_logs', [
            'table_name' => $tableName,
            'record_id' => $recordId,
            'action' => $action,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'user_id' => $actor?->id,
            'user_name' => $actor?->username,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => new Expression('NOW()'),
        ])->execute();
    }

    public function getHistory(
        string $tableName,
        int $recordId,
        ?int $limit = null
    ): array {
        $query = (new Query($this->db))
            ->from('audit_logs')
            ->where(['table_name' => $tableName, 'record_id' => $recordId])
            ->orderBy(['created_at' => SORT_DESC]);

        if ($limit) {
            $query->limit($limit);
        }

        return $query->all();
    }

    public function getUserActivity(
        int $userId,
        ?\DateTimeInterface $from = null,
        ?\DateTimeInterface $to = null
    ): array {
        $query = (new Query($this->db))
            ->from('audit_logs')
            ->where(['user_id' => $userId])
            ->orderBy(['created_at' => SORT_DESC]);

        if ($from) {
            $query->andWhere(['>=', 'created_at', $from->format('Y-m-d H:i:s')]);
        }

        if ($to) {
            $query->andWhere(['<=', 'created_at', $to->format('Y-m-d H:i:s')]);
        }

        return $query->all();
    }
}
```

## Usage Examples

### 1. Manual Audit Logging

```php
<?php
// In a service or controller

use App\Domain\Common\Audit\AuditService;

class BrandService
{
    public function __construct(
        private AuditService $auditService,
        private CurrentUser $currentUser
    ) {}

    public function deleteBrand(int $id): void
    {
        $brand = Brand::findOne($id);
        if ($brand) {
            $oldValues = $brand->getAttributes();
            
            // Delete the brand
            $brand->delete();
            
            // Log audit manually
            $this->auditService->log(
                'brands',
                $id,
                'DELETE',
                $oldValues,
                null,
                $this->currentUser->getActor(),
                $this->getClientIp(),
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );
        }
    }
}
```

### 2. Querying Audit History

```php
<?php
// Get audit history for a record
$history = $auditService->getHistory('brands', $brandId);

foreach ($history as $entry) {
    echo sprintf(
        "Action: %s by %s at %s\n",
        $entry['action'],
        $entry['user_name'],
        $entry['created_at']
    );
    
    if ($entry['old_values']) {
        echo "Old values: " . $entry['old_values'] . "\n";
    }
    
    if ($entry['new_values']) {
        echo "New values: " . $entry['new_values'] . "\n";
    }
}
```

## Performance Considerations

### 1. Selective Auditing

Only audit critical tables and sensitive operations:

```php
// In your model
public function behaviors(): array
{
    return [
        'audit' => [
            'class' => AuditBehavior::class,
            'except' => ['last_accessed', 'view_count'], // Exclude non-sensitive fields
        ],
    ];
}
```

### 2. Batch Operations

For bulk operations, consider disabling audit:

```php
// Disable audit for bulk operations
Brand::disableAudit();
try {
    // Perform bulk operations
    Brand::updateAll(['status' => 'inactive'], ['condition' => $condition]);
} finally {
    // Re-enable audit
    Brand::enableAudit();
}
```

### 3. Archive Strategy

Archive old audit records periodically:

```sql
-- Archive records older than 1 year
CREATE TABLE audit_logs_archive (LIKE audit_logs INCLUDING ALL);

INSERT INTO audit_logs_archive 
SELECT * FROM audit_logs 
WHERE created_at < NOW() - INTERVAL '1 year';

DELETE FROM audit_logs 
WHERE created_at < NOW() - INTERVAL '1 year';
```

## Security Considerations

1. **Sensitive Data**: Exclude sensitive fields (passwords, tokens) from audit logs
2. **Access Control**: Restrict access to audit logs to authorized personnel
3. **Data Retention**: Implement appropriate data retention policies
4. **Encryption**: Consider encrypting sensitive audit data

## Monitoring and Alerts

### 1. Audit Volume Monitoring

```sql
-- Monitor audit log volume
SELECT 
    DATE(created_at) as date,
    COUNT(*) as record_count,
    action
FROM audit_logs 
WHERE created_at >= NOW() - INTERVAL '7 days'
GROUP BY DATE(created_at), action
ORDER BY date DESC;
```

### 2. Suspicious Activity Detection

```sql
-- Detect unusual activity patterns
SELECT 
    user_id,
    user_name,
    COUNT(*) as action_count,
    MIN(created_at) as first_action,
    MAX(created_at) as last_action
FROM audit_logs 
WHERE created_at >= NOW() - INTERVAL '1 hour'
GROUP BY user_id, user_name
HAVING COUNT(*) > 100  -- More than 100 actions in 1 hour
ORDER BY action_count DESC;
```

## API Endpoints for Audit

### 1. Get Record History

```php
// GET /v1/brands/{id}/history
public function getHistory(int $id): ResponseInterface
{
    $history = $this->auditService->getHistory('brands', $id);
    return $this->responseFactory->success($history);
}
```

### 2. Get User Activity

```php
// GET /v1/audit/activity?user_id={id}&from={date}&to={date}
public function getUserActivity(Request $request): ResponseInterface
{
    $userId = (int) $request->getQueryParams()['user_id'];
    $from = $request->getQueryParams()['from'] 
        ? new \DateTime($request->getQueryParams()['from']) 
        : null;
    $to = $request->getQueryParams()['to'] 
        ? new \DateTime($request->getQueryParams()['to']) 
        : null;

    $activity = $this->auditService->getUserActivity($userId, $from, $to);
    return $this->responseFactory->success($activity);
}
```

## Testing

### 1. Unit Tests

```php
<?php
// tests/unit/AuditServiceTest.php

declare(strict_types=1);

use App\Domain\Common\Audit\AuditService;

final class AuditServiceTest extends TestCase
{
    public function testLogInsert(): void
    {
        $auditService = new AuditService($this->db);
        
        $auditService->log(
            'test_table',
            1,
            'INSERT',
            null,
            ['name' => 'Test'],
            new Actor(id: 1, username: 'test'),
            '127.0.0.1',
            'Test-Agent'
        );
        
        $record = $this->db->createQuery()
            ->from('audit_logs')
            ->where(['table_name' => 'test_table', 'record_id' => 1])
            ->one();
            
        $this->assertNotNull($record);
        $this->assertEquals('INSERT', $record['action']);
    }
}
```

This audit logging strategy provides comprehensive tracking of data changes with automatic logging via traits, manual logging capabilities, and querying features for compliance and debugging purposes.
