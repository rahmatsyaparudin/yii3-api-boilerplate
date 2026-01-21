<?php

declare(strict_types=1);

namespace App\Shared\Service;

use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\BadRequestException;

/**
 * Base Service for all domain services
 * 
 * Menyediakan common functionality yang sering digunakan:
 * - Parameter handling
 * - Query building
 * - Error handling
 * - Response formatting
 */
abstract class BaseService
{
    /**
     * Extract pagination parameters from request data
     */
    protected function extractPagination(array $params): array
    {
        return [
            'page' => $params['page'] ?? 1,
            'limit' => $params['page_size'] ?? 20,
        ];
    }

    /**
     * Extract sorting parameters from request data
     */
    protected function extractSorting(array $params): array
    {
        return [
            'by' => $params['sort']['by'] ?? 'name',
            'dir' => $params['sort']['dir'] ?? 'asc',
        ];
    }

    /**
     * Extract filter parameters from request data
     */
    protected function extractFilter(array $params): array
    {
        return $params['filter'] ?? [];
    }

    /**
     * Build standard change log for audit trail
     */
    protected function buildChangeLog(array $extra = []): array
    {
        return array_merge([
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => 'api_user', // TODO: Get from authenticated user
            'updated_at' => null,
            'updated_by' => null,
            'deleted_at' => null,
            'deleted_by' => null,
        ], $extra);
    }

    /**
     * Format list response with pagination
     */
    protected function formatListResponse(array $items, array $paginationMeta): array
    {
        return [
            'items' => array_map(fn($item) => $item->toArray(), $items),
            'pagination' => $paginationMeta,
        ];
    }

    /**
     * Format list response with custom item key
     */
    protected function formatListResponseWithKey(array $items, array $paginationMeta, string $itemKey = 'items'): array
    {
        return [
            $itemKey => array_map(fn($item) => $item->toArray(), $items),
            'pagination' => $paginationMeta,
        ];
    }

    /**
     * Validate required fields in data
     */
    protected function validateRequired(array $data, array $requiredFields): void
    {
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new BadRequestException("Field '{$field}' is required");
            }
        }
    }

    /**
     * Get default status for new entities
     */
    protected function getDefaultStatus(): int
    {
        return 1; // Default to active/draft status
    }

    /**
     * Check if entity exists by name (common validation)
     */
    protected function checkNameExists(string $name, callable $existsCallback): void
    {
        if ($existsCallback($name)) {
            throw new ConflictException("Entity with name '{$name}' already exists");
        }
    }

    /**
     * Handle not found error
     */
    protected function handleNotFound(int $id, string $entityName): void
    {
        throw new NotFoundException("{$entityName} with ID {$id} not found");
    }

    /**
     * Handle business rule violation
     */
    protected function handleBusinessRule(string $rule, string $context = ''): void
    {
        $message = $context ? "Business rule violation in {$context}: {$rule}" : "Business rule violation: {$rule}";
        throw new BadRequestException($message);
    }
}
