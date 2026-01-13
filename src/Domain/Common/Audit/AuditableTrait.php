<?php

declare(strict_types=1);

namespace App\Domain\Common\Audit;

use Yiisoft\Db\Query\Query;

trait AuditableTrait
{
    protected array $oldAttributes = [];
    protected bool $auditEnabled   = true;

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

    protected function logAudit(?string $action = null): void
    {
        $action ??= $this->getIsNewRecord() ? 'INSERT' : 'UPDATE';
        $tableName = $this->getTableName();
        $recordId  = (int) $this->getPrimaryKey();

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
            'record_id'  => $recordId,
            'action'     => $action,
            'old_values' => $oldValues ? \json_encode($oldValues) : null,
            'new_values' => $newValues ? \json_encode($newValues) : null,
            'user_id'    => $actor?->id,
            'user_name'  => $actor?->username,
            'ip_address' => $this->getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => new \Yiisoft\Db\Expression\Expression('NOW()'),
        ])->execute();
    }

    protected function getCurrentActor(): ?Actor
    {
        // Get current actor from CurrentUser service
        // This requires dependency injection or service locator
        try {
            // Try to get from container if available
            if (\class_exists('\Yiisoft\Di\Container')) {
                $container = \Yiisoft\Di\Container::get(\App\Infrastructure\Security\CurrentUser::class);

                return $container->getActor();
            }
        } catch (\Throwable $e) {
            // Fallback to null if container not available
        }

        return null;
    }

    protected function getClientIp(): ?string
    {
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = \explode(',', $_SERVER[$header]);

                return \trim($ips[0]);
            }
        }

        return null;
    }
}
