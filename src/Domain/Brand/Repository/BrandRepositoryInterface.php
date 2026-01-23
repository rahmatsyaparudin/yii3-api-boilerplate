<?php

declare(strict_types=1);

namespace App\Domain\Brand\Repository;

use App\Domain\Brand\Entity\Brand;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * Brand Repository Interface
 * 
 * Pure repository pattern for Brand aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface BrandRepositoryInterface
{
    /**
     * Find brand by primary ID
     * 
     * Returns the brand entity if found, null otherwise.
     * This method should only return non-deleted brands by default
     * unless the repository implementation specifically includes deleted records.
     * 
     * @param int $id Brand primary identifier
     * @return Brand|null Brand entity or null if not found
     * 
     * @example
     * ```php
     * $brand = $repository->findById(1);
     * if ($brand === null) {
     *     throw new NotFoundException(
     *         translate: new Message(
     *             key: 'resource.not_found',
     *             params: [
     *                 'resource' => 'Brand',
     *                 'field' => 'id',
     *                 'value' => 1
     *             ]
     *         )
     *     );
     * }
     * 
     * // Check if brand can be updated
     * if (!$brand->canBeUpdated()) {
     *     throw new BadRequestException('Brand cannot be updated');
     * }
     * ```
     */
    public function findById(int $id): ?Brand;

    /**
     * Find brand by unique name
     * 
     * Searches for brand by name field. Returns null if not found.
     * Useful for validation and uniqueness checks.
     * 
     * @param string $name Brand name to search for
     * @return Brand|null Brand entity or null if not found
     * 
     * @example
     * ```php
     * $brand = $repository->findByName('Nike');
     * if ($brand !== null) {
     *     // Brand exists, handle update or show error
     *     return BrandResponse::fromEntity($brand);
     * }
     * 
     * // Create new brand if not found
     * $newBrand = Brand::create('Nike', Status::draft(), $details);
     * return BrandResponse::fromEntity($repository->saveBrand($newBrand));
     * ```
     */
    public function findByName(string $name): ?Brand;

    /**
     * Check if brand exists by name
     * 
     * Optimized method for existence checking. Returns boolean
     * instead of full entity for better performance.
     * 
     * @param string $name Brand name to check
     * @return bool True if brand exists, false otherwise
     * 
     * @example
     * ```php
     * // Validation in application service
     * if ($repository->existsByName($command->name)) {
     *     throw new BadRequestException('Brand with this name already exists');
     * }
     * 
     * // Conditional logic
     * $canCreate = !$repository->existsByName('Adidas');
     * if ($canCreate) {
     *     // Proceed with creation
     * }
     * ```
     */
    public function existsByName(string $name): bool;

    /**
     * Save brand aggregate root
     * 
     * Persists brand entity to storage. Handles both insert
     * and update operations automatically based on entity state.
     * Returns the saved entity with updated ID for new entities.
     * 
     * @param Brand $brand Brand entity to save
     * @return Brand Saved brand entity with proper ID
     * 
     * @example
     * ```php
     * // Creating new brand
     * $newBrand = Brand::create('Puma', Status::draft(), $details);
     * $savedBrand = $repository->save($newBrand);
     * echo $savedBrand->getId(); // Now has database ID
     * 
     * // Updating existing brand
     * $brand->updateDetailInfo($newDetails);
     * $brand->transitionTo(Status::active());
     * $updatedBrand = $repository->save($brand);
     * ```
     */
    public function save(Brand $brand): Brand;

    /**
     * Delete brand aggregate root (soft delete)
     * 
     * Performs soft delete by changing status to DELETED.
     * Entity remains in storage but marked as deleted.
     * Returns the updated entity with deleted status.
     * 
     * @param Brand $brand Brand entity to delete
     * @return Brand Updated brand entity with deleted status
     * 
     * @example
     * ```php
     * // Check if can be deleted
     * if (!$brand->canBeDeleted()) {
     *     throw new BadRequestException('Brand cannot be deleted in current status');
     * }
     * 
     * // Perform soft delete
     * $deletedBrand = $repository->delete($brand);
     * 
     * // Verify deletion
     * assert($deletedBrand->getStatus()->isDeleted());
     * ```
     */
    public function delete(Brand $brand): Brand;

    /**
     * Find soft-deleted brand by ID
     * 
     * Searches for brand that has been marked as deleted.
     * Returns null if brand not found or not deleted.
     * Note: This method only finds deleted brands, doesn't restore status.
     * 
     * @param int $id ID of deleted brand to find
     * @return Brand|null Deleted brand entity or null if not found
     * 
     * @example
     * ```php
     * $deletedBrand = $repository->restore(1);
     * if ($deletedBrand === null) {
     *     throw new NotFoundException(
     *         translate: new Message(
     *             key: 'resource.not_found',
     *             params: [
     *                 'resource' => 'Brand',
     *                 'field' => 'id',
     *                 'value' => 1
     *             ]
     *         )
     *     );
     * }
     *     
     * // Brand still has DELETED status
     * assert($deletedBrand->getStatus()->isDeleted());
     *     
     * // To actually restore, you need to update status
     * $deletedBrand->markAsRestored(); // Changes to draft status
     * $restoredBrand = $repository->save($deletedBrand);
     *     
     * // Now brand is restored and usable
     * assert($restoredBrand->getStatus()->isDraft());
     * ```
     */
    public function restore(int $id): ?Brand;

    /**
     * List brands with filtering, pagination, and sorting
     * 
     * Returns paginated results based on search criteria.
     * Supports filtering by status, name, and other fields.
     * Includes pagination metadata and sorting options.
     * 
     * @param SearchCriteria $criteria Search, filter, and pagination parameters
     * @return PaginatedResult Paginated brand list with metadata
     * 
     * @example
     * ```php
     * // Basic pagination
     * $criteria = SearchCriteria::fromArray([
     *     'page' => 1,
     *     'pageSize' => 20
     * ]);
     * $result = $repository->list($criteria);
     * 
     * echo "Total brands: " . $result->total;
     * echo "Page: " . $result->page . " of " . $result->pageCount;
     * 
     * // With filtering
     * $criteria = SearchCriteria::fromArray([
     *     'page' => 1,
     *     'pageSize' => 10,
     *     'filter' => [
     *         'status' => Status::ACTIVE->value,
     *         'name' => 'Nike' // Partial match
     *     ],
     *     'sort' => [
     *         'name' => 'asc',
     *         'id' => 'desc'
     *     ]
     * ]);
     * $activeBrands = $repository->list($criteria);
     * 
     * // Process results
     * foreach ($activeBrands->data as $brand) {
     *     echo $brand->getName() . ' - ' . $brand->getStatus()->label();
     * }
     * ```
     */
    public function list(SearchCriteria $criteria): PaginatedResult;
}
