<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObject;

use App\Shared\Exception\BadRequestException;
use App\Domain\Shared\Contract\DateTimeProviderInterface;
use App\Domain\Shared\Concerns\Entity\Auditable;

/**
 * Generic Detail Info Value Object
 * 
 * Menyimpan data detail dalam format JSON/array
 * Reusable untuk semua domain yang membutuhkan flexible data storage
 */
final readonly class DetailInfo
{
    use Auditable;

    public function __construct(public readonly array $data) {}

    public static function createWithAudit(
        DateTimeProviderInterface $dateTime,
        string $user,
        array $payload = [] 
    ): self {
        $createdLog = (new self([]))->createAuditStamp($dateTime, $user);
        return new self(array_merge($payload, $createdLog));
    }

    public static function updateWithAudit(
        DateTimeProviderInterface $dateTime,
        string $user,
        array $currentLog,
        array $payload = [] 
    ): self {
        $updatedLog = self::updateAuditStamp($currentLog, $dateTime, $user);
        
        unset($payload['change_log']);
        $mergedData = array_merge($payload, ['change_log' => $updatedLog]);
        
        return new self($mergedData);
    }

    public static function deleteWithAudit(
        DateTimeProviderInterface $dateTime,
        string $user,
        array $currentLog,
        array $payload = [] 
    ): self {
        $deletedLog = self::deleteAuditStamp($currentLog, $dateTime, $user);
        
        unset($payload['change_log']); 
        $mergedData = array_merge($payload, ['change_log' => $deletedLog]);
        
        return new self($mergedData);
    }

    public static function restoreWithAudit(
        DateTimeProviderInterface $dateTime,
        string $user,
        array $currentLog,
        array $payload = [] 
    ): self {
        $restoredLog = self::restoreAuditStamp($currentLog, $dateTime, $user);
        
        unset($payload['change_log']);
        $mergedData = array_merge($payload, ['change_log' => $restoredLog]);
        
        return new self($mergedData);
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * Get value by key
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Check if key exists
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Get all data as array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Get all data as JSON string
     */
    public function toJson(): string
    {
        return json_encode($this->data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
