<?php

declare(strict_types=1);

namespace App\Shared\Validation;

/**
 * Validation Context Constants
 * 
 * This class provides predefined constants for different validation contexts
 * throughout the application. These contexts help determine which validation
 * rules should be applied based on the current operation or business scenario.
 * 
 * @package App\Shared\Validation
 * 
 * @example
 * // Using context in validator
 * $validator = new UserValidator();
 * $validator->validate($data, ValidationContext::CREATE);
 * 
 * @example
 * // Context-based validation rules
 * public function getValidationRules(string $context): array
 * {
 *     return match($context) {
 *         ValidationContext::CREATE => $this->getCreateRules(),
 *         ValidationContext::UPDATE => $this->getUpdateRules(),
 *         ValidationContext::SEARCH => $this->getSearchRules(),
 *         default => $this->getDefaultRules()
 *     };
 * }
 * 
 * @example
 * // In service layer
 * public function createUser(array $data): User
 * {
 *     $this->validator->validate($data, ValidationContext::CREATE);
 *     return $this->repository->create($data);
 * }
 * 
 * @example
 * // Conditional validation based on context
 * if ($context === ValidationContext::UPDATE) {
 *     $this->validateId($data['id']);
 *     $this->validateOwnership($data['id'], $userId);
 * }
 * 
 * @example
 * // In API endpoints
 * public function updateAction(ServerRequestInterface $request): ResponseInterface
 * {
 *     $data = $request->getParsedBody();
 *     $this->validator->validate($data, ValidationContext::UPDATE);
 *     // Process update...
 * }
 */
final class ValidationContext
{
    /**
     * Search validation context
     * 
     * Used for validating search parameters, filters, and query conditions.
     * Typically applies minimal validation to allow flexible searching.
     */
    public const SEARCH = 'search';

    /**
     * Create validation context
     * 
     * Used for validating data when creating new resources.
     * Usually applies the most comprehensive validation rules.
     */
    public const CREATE = 'create';

    /**
     * Update validation context
     * 
     * Used for validating data when updating existing resources.
     * May allow partial updates and exclude certain fields.
     */
    public const UPDATE = 'update';

    /**
     * Delete validation context
     * 
     * Used for validating delete operations.
     * Typically validates resource existence and permissions.
     */
    public const DELETE = 'delete';

    /**
     * Approve validation context
     * 
     * Used for validating approval operations.
     * Validates business rules and authorization for approval.
     */
    public const APPROVE = 'approve';

    /**
     * Reject validation context
     * 
     * Used for validating rejection operations.
     * Validates business rules and authorization for rejection.
     */
    public const REJECT = 'reject';

    /**
     * Get all available contexts
     * 
     * Returns an array of all defined validation contexts.
     * Useful for documentation, testing, or validation of context values.
     * 
     * @return array List of all validation contexts
     * 
     * @example
     * // Get all contexts for testing
     * $contexts = ValidationContext::getAllContexts();
     * foreach ($contexts as $context) {
     *     $this->assertValidationRulesExist($context);
     * }
     * 
     * @example
     * // For API documentation
     * $availableContexts = ValidationContext::getAllContexts();
     * $this->documentValidationContexts($availableContexts);
     * 
     * @example
     * // Validate context parameter
     * if (!in_array($requestedContext, ValidationContext::getAllContexts())) {
     *     throw new InvalidArgumentException('Invalid validation context');
     * }
     */
    public static function getAllContexts(): array
    {
        return [
            self::SEARCH,
            self::CREATE,
            self::UPDATE,
            self::DELETE,
            self::APPROVE,
            self::REJECT,
        ];
    }

    /**
     * Check if context is valid
     * 
     * Validates whether the provided context string is one of the
     * defined validation contexts.
     * 
     * @param string $context Context to validate
     * @return bool True if context is valid
     * 
     * @example
     * // Validate context
     * if (ValidationContext::isValid($context)) {
     *     $this->applyValidation($data, $context);
     * }
     * 
     * @example
     * // In middleware
     * $context = $request->getAttribute('validation_context');
     * if (!ValidationContext::isValid($context)) {
     *     throw new BadRequestException('Invalid validation context');
     * }
     * 
     * @example
     * // Default context fallback
     * $context = $requestedContext ?? ValidationContext::CREATE;
     * if (!ValidationContext::isValid($context)) {
     *     $context = ValidationContext::CREATE;
     * }
     */
    public static function isValid(string $context): bool
    {
        return in_array($context, self::getAllContexts(), true);
    }

    /**
     * Get context description
     * 
     * Returns a human-readable description of the validation context.
     * Useful for documentation, logging, or error messages.
     * 
     * @param string $context Context to describe
     * @return string Context description
     * 
     * @example
     * // Get context description for logging
     * $description = ValidationContext::getDescription($context);
     * $this->logger->info("Validating in context: {$description}");
     * 
     * @example
     * // In error messages
     * $description = ValidationContext::getDescription($context);
     * throw new ValidationException("Validation failed in {$description} context");
     * 
     * @example
     * // For API documentation
     * foreach (ValidationContext::getAllContexts() as $context) {
     *     $descriptions[$context] = ValidationContext::getDescription($context);
     * }
     */
    public static function getDescription(string $context): string
    {
        return match($context) {
            self::SEARCH => 'Search and filtering operations',
            self::CREATE => 'Resource creation operations',
            self::UPDATE => 'Resource update operations',
            self::DELETE => 'Resource deletion operations',
            self::APPROVE => 'Resource approval operations',
            self::REJECT => 'Resource rejection operations',
            default => 'Unknown validation context'
        };
    }
}
