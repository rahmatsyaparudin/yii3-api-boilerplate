<?php

declare(strict_types=1);

namespace App\Infrastructure\Concerns;

// Domain Layer
use App\Domain\Shared\Contract\CurrentUserInterface;

// Vendor Layer
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;

/**
 * Trait Auditable menyediakan kemampuan pencatatan log otomatis
 * untuk class berbasis Active Record di layer Infrastructure.
 */
trait Auditable
{
    protected array $oldAttributes = [];
    protected bool $auditEnabled = true;

    // Helper untuk mendapatkan Service secara dinamis di level Infrastructure
    // Karena Active Record tidak mendukung Constructor Injection secara default
    abstract protected function getCurrentUser(): CurrentUserInterface;

    public function beforeSave(): bool
    {
        if ($this->auditEnabled && !$this->getIsNewRecord()) {
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

    protected function logAudit(?string $action = null): void
    {
        $action ??= $this->getIsNewRecord() ? 'INSERT' : 'UPDATE';
        
        $oldValues = ($action === 'UPDATE' || $action === 'DELETE') ? $this->oldAttributes : null;
        $newValues = ($action !== 'DELETE') ? $this->getAttributes() : null;

        $this->insertAuditLog(
            $this->getTableName(),
            (int) $this->getPrimaryKey(),
            $action,
            $oldValues,
            $newValues
        );
    }

    protected function insertAuditLog(
        string $tableName,
        int $recordId,
        string $action,
        ?array $oldValues,
        ?array $newValues
    ): void {
        $actor = $this->getCurrentUser()->getActor();

        (new Query($this->getDb()))->createCommand()->insert('audit_logs', [
            'table_name' => $tableName,
            'record_id'  => $recordId,
            'action'     => $action,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'user_id'    => $actor->getId(),
            'user_name'  => $actor->getUsername(),
            'ip_address' => $this->getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => new Expression('NOW()'),
        ])->execute();
    }

    private function getClientIp(): ?string
    {
        $keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                return trim(explode(',', $_SERVER[$key])[0]);
            }
        }
        return null;
    }
}