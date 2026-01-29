<?php

declare(strict_types=1);

namespace App\Shared\Enums;

/**
 * Application Constants Enumeration
 * 
 * This class provides centralized constants for application-wide usage including
 * synchronization/locking patterns, validation regex patterns, and common query filters.
 * It ensures consistency across the application and provides type-safe access to constants.
 * 
 * @package App\Shared\Enums
 * 
 * @example
 * // Using optimistic locking constants
 * $entity->lock_version = 1;
 * if ($entity->lock_version !== $request->get(AppConstants::OPTIMISTIC_LOCK)) {
 *     throw new OptimisticLockException('Record has been modified');
 * }
 * 
 * @example
 * // Using synchronization constants
 * $masterData = $this->getMasterData();
 * $slaveData = $this->getSlaveData();
 * 
 * // Sync from master to slave
 * $this->syncData($masterData, $slaveData, AppConstants::SYNC_MASTER);
 * 
 * @example
 * // Using validation patterns
 * if (!preg_match(AppConstants::DECIMAL_PATTERN, $amount)) {
 *     throw new ValidationException('Invalid decimal format');
 * }
 * 
 * @example
 * // Using status filters in queries
 * $query->andWhere(AppConstants::statusNotDeleted());
 * // Equivalent to: ['<>', 'status', 'deleted']
 */
final class AppConstants
{
    // -------------------------------------------------------------------------
    // Synchronization / Locking Constants
    // -------------------------------------------------------------------------
    
    /**
     * Optimistic locking version field name
     * Used for implementing optimistic locking pattern to prevent concurrent modifications
     */
    public const OPTIMISTIC_LOCK = 'lock_version';
    
    /**
     * MongoDB synchronization identifier
     * Used for identifying MongoDB sync operations
     */
    public const SYNC_MONGODB = 'sync_mdb';
    
    /**
     * Master database synchronization flag
     * Used for master-slave synchronization operations
     */
    public const SYNC_MASTER = 'sync_master';
    
    /**
     * Slave database synchronization flag
     * Used for master-slave synchronization operations
     */
    public const SYNC_SLAVE = 'sync_slave';
    
    /**
     * Slave identifier field name
     * Used for identifying slave records in distributed systems
     */
    public const SLAVE_ID = 'slave_id';
    
    /**
     * Master identifier field name
     * Used for identifying master records in distributed systems
     */
    public const MASTER_ID = 'master_id';

    // -------------------------------------------------------------------------
    // Validation Patterns
    // -------------------------------------------------------------------------
    
    /**
     * Decimal number validation pattern
     * Matches numbers with optional 2 decimal places (e.g., 123, 123.45, 123.4)
     */
    public const DECIMAL_PATTERN = '/^\d+(\.\d{1,2})?$/';

    // -------------------------------------------------------------------------
    // Filtered Statuses
    // -------------------------------------------------------------------------
    
    /**
     * Query condition for "status is not deleted"
     * 
     * Returns a query condition array that excludes deleted records.
     * This is commonly used in repository queries to filter out soft-deleted records.
     * 
     * @return array{0:string,1:string,2:int} Query condition array for not deleted status
     * 
     * @example
     * // In repository query
     * $query->andWhere(AppConstants::statusNotDeleted());
     * // Generates: ['<>', 'status', 3] (assuming RecordStatus::DELETED->value = 3)
     * 
     * @example
     * // In service layer
     * $criteria = new SearchCriteria(
     *     filter: AppConstants::statusNotDeleted(),
     *     page: 1,
     *     pageSize: 10
     * );
     * 
     * @example
     * // Manual query building
     * $condition = AppConstants::statusNotDeleted();
     * $results = $this->db->createQueryBuilder()
     *     ->select('*')
     *     ->from('users')
     *     ->where($condition[0], $condition[1], $condition[2])
     *     ->fetchAll();
     */
    public static function statusNotDeleted(): array
    {
        return ['<>', 'status', RecordStatus::DELETED->value];
    }
}
