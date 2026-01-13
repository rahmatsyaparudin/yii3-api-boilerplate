<?php

declare(strict_types=1);

namespace App\Domain\Common\Audit;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

final class AuditService
{
    public function __construct(private ConnectionInterface $db)
    {
    }

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
        (new Query($this->db))->createCommand($this->db)->insert('audit_logs', [
            'table_name' => $tableName,
            'record_id'  => $recordId,
            'action'     => $action,
            'old_values' => $oldValues ? \json_encode($oldValues) : null,
            'new_values' => $newValues ? \json_encode($newValues) : null,
            'user_id'    => $actor?->id,
            'user_name'  => $actor?->username,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => new \Yiisoft\Db\Expression\Expression('NOW()'),
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
