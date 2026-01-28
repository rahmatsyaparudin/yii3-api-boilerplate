<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObject;

// Shared Layer
use App\Shared\Exception\BadRequestException;

// Domain Layer
use App\Domain\Shared\Contract\DateTimeProviderInterface;
use App\Domain\Shared\Concerns\Entity\ChangeLogged;

/**
 * Generic Detail Info Value Object
 * 
 * Menyimpan data detail dalam format JSON/array
 * Reusable untuk semua domain yang membutuhkan flexible data storage
 */
final readonly class DetailInfo
{
    use ChangeLogged;

    public function __construct(public readonly array $data) {}

    public static function createdLog(
        DateTimeProviderInterface $dateTime,
        string $user,
        array $payload = [] 
    ): self {
        $createdLog = (new self([]))->createdChangeLog($dateTime, $user);
        return new self(array_merge($payload, $createdLog));
    }

    public static function updatedLog(
        DateTimeProviderInterface $dateTime,
        string $user,
        array $currentLog,
        array $payload = [] 
    ): self {
        $updatedLog = self::updatedChangeLog($currentLog, $dateTime, $user);
        
        unset($payload['change_log']);
        $mergedData = array_merge($payload, ['change_log' => $updatedLog]);
        
        return new self($mergedData);
    }

    public static function deletedLog(
        DateTimeProviderInterface $dateTime,
        string $user,
        array $currentLog,
        array $payload = [] 
    ): self {
        $deletedLog = self::deletedChangeLog($currentLog, $dateTime, $user);
        
        unset($payload['change_log']); 
        $mergedData = array_merge($payload, ['change_log' => $deletedLog]);
        
        return new self($mergedData);
    }

    public static function restoredLog(
        DateTimeProviderInterface $dateTime,
        string $user,
        array $currentLog,
        array $payload = [] 
    ): self {
        $restoredLog = self::restoredChangeLog($currentLog, $dateTime, $user);
        
        unset($payload['change_log']);
        $mergedData = array_merge($payload, ['change_log' => $restoredLog]);
        
        return new self($mergedData);
    }

    public function with(array $additionalLog): self
    {
        $data = $this->toArray();
        
        $data['change_log'] = array_merge(
            $data['change_log'] ?? [],
            $additionalLog
        );

        return new self($data);
    }

    public static function approvedLog(
        DateTimeProviderInterface $dateTime,
        string $user,
        array $currentLog,
        array $payload = [] 
    ): self {
        $approvedLog = self::approvedChangeLog($currentLog, $dateTime, $user);
        
        unset($payload['change_log']);
        $mergedData = array_merge($payload, ['change_log' => $approvedLog]);
        
        return new self($mergedData);
    }

    public static function rejectedLog(
        DateTimeProviderInterface $dateTime,
        string $user,
        array $currentLog,
        array $payload = [] 
    ): self {
        $rejectedLog = self::rejectedChangeLog($currentLog, $dateTime, $user);
        
        unset($payload['change_log']);
        $mergedData = array_merge($payload, ['change_log' => $rejectedLog]);
        
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
     * Create from JSON string
     */
    public static function fromJson(?string $jsonString): self
    {
        if (empty($jsonString)) {
            return new self([]);
        }
        
        $data = json_decode($jsonString, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new self([]);
        }
        
        return new self($data ?: []);
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