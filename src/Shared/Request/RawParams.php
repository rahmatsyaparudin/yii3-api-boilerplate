<?php

declare(strict_types=1);

namespace App\Shared\Request;

// PSR Interfaces
use Psr\Http\Message\ServerRequestInterface;

// Shared Layer
use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;
use App\Shared\Security\InputSanitizer;

/**
 * Raw Request Parameters Value Object
 * 
 * This readonly class provides a type-safe interface for handling raw request
 * parameters with validation, filtering, and sanitization capabilities. It serves
 * as a foundation for other parameter classes and follows DDD value object patterns.
 * 
 * @package App\Shared\Request
 * 
 * @example
 * // Basic usage with array data
 * $params = new RawParams(['name' => 'John', 'age' => 30]);
 * echo $params->get('name'); // 'John'
 * echo $params->has('email'); // false
 * 
 * @example
 * // Creating from PSR request
 * $params = new RawParams($request->getQueryParams());
 * $search = $params->get('search', '');
 * $page = $params->get('page', 1);
 * 
 * @example
 * // With default values
 * $params = new RawParams();
 * $name = $params->get('name', 'Anonymous');
 * $limit = $params->get('limit', 50);
 * 
 * @example
 * // In middleware processing
 * $rawData = array_merge($request->getQueryParams(), $request->getParsedBody() ?? []);
 * $params = new RawParams($rawData);
 * 
 * @example
 * // For validation and filtering
 * $allowedKeys = ['name', 'email', 'status'];
 * $params = new RawParams($requestData)->onlyAllowed($allowedKeys);
 * 
 * @example
 * // For security sanitization
 * $params = new RawParams($userInput)->sanitize();
 * $cleanData = $params->all();
 */
final readonly class RawParams
{
    /**
     * Raw Parameters constructor
     * 
     * Creates a new RawParams object with the provided parameter array.
     * The parameters are stored immutably and cannot be modified after creation.
     * 
     * @param array $params Associative array of parameters
     * 
     * @example
     * // Empty parameters
     * $params = new RawParams();
     * 
     * @example
     * // With initial data using named arguments
     * $params = new RawParams(
     *     params: [
     *         'name' => 'John Doe',
     *         'email' => 'john@example.com',
     *         'status' => 'active'
     *     ]
     * );
     * 
     * @example
     * // From request data
     * $params = new RawParams($request->getParsedBody());
     * 
     * @example
     * // From merged query and body
     * $params = new RawParams(
     *     params: array_merge($request->getQueryParams(), $request->getParsedBody() ?? [])
     * );
     */
    public function __construct(
        private array $params = []
    ) {
    }

    /**
     * Get a parameter value by key
     * 
     * Returns the value associated with the specified key, or the default
     * value if the key doesn't exist. Provides type-safe parameter access.
     * 
     * @param string $key Parameter key to retrieve
     * @param mixed $default Default value if key doesn't exist
     * @return mixed Parameter value or default
     * 
     * @example
     * // Get parameter with default
     * $name = $params->get('name', 'Anonymous');
     * $page = $params->get('page', 1);
     * $limit = $params->get('limit', 50);
     * 
     * @example
     * // In controller
     * public function listAction(RawParams $params): array
     * {
     *     $search = $params->get('search', '');
     *     $category = $params->get('category', 'all');
     *     return $this->service->search($search, $category);
     * }
     * 
     * @example
     * // Type casting
     * $page = (int) $params->get('page', 1);
     * $active = (bool) $params->get('active', false);
     * $ids = array_filter(explode(',', $params->get('ids', '')));
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    /**
     * Check if a parameter key exists
     * 
     * Returns true if the specified key exists in the parameters array,
     * regardless of its value (including null values).
     * 
     * @param string $key Parameter key to check
     * @return bool True if key exists
     * 
     * @example
     * // Check for parameter existence
     * if ($params->has('search')) {
     *     $searchTerm = $params->get('search');
     *     // Apply search logic
     * }
     * 
     * @example
     * // Conditional processing
     * if ($params->has('category')) {
     *     $category = $params->get('category');
     *     $query->andWhere(['category' => $category]);
     * }
     * 
     * @example
     * // Feature flags
     * if ($params->has('debug')) {
     *     $this->enableDebugMode();
     * }
     * 
     * @example
     * // Required parameter validation
     * if (!$params->has('user_id')) {
     *     throw new BadRequestException('user_id is required');
     * }
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->params);
    }

    /**
     * Convert parameters to array
     * 
     * Returns a copy of all parameters as an associative array.
     * Provides a safe way to access all parameter data.
     * 
     * @return array All parameters as associative array
     * 
     * @example
     * // Get all parameters
     * $allParams = $params->toArray();
     * 
     * @example
     * // For logging
     * $this->logger->info('Request parameters', $params->toArray());
     * 
     * @example
     * // For API responses
     * return [
     *     'data' => $results,
     *     'parameters' => $params->toArray()
     * ];
     * 
     * @example
     * // For caching
     * $cacheKey = md5(serialize($params->toArray()));
     */
    public function toArray(): array
    {
        return $this->params;
    }

    /**
     * Get all parameters (alias for toArray)
     * 
     * Returns all parameters as an associative array.
     * This is an alias method for consistency with other parameter classes.
     * 
     * @return array All parameters as associative array
     * 
     * @example
     * // Get all parameters
     * $allParams = $params->all();
     * 
     * @example
     * // In service layer
     * public function processParams(RawParams $params): void
     * {
     *     $data = $params->all();
     *     $this->processor->process($data);
     * }
     * 
     * @example
     * // For debugging
     * var_dump($params->all());
     */
    public function all(): array
    {
        return $this->params;
    }

    /**
     * Create new instance with additional parameter
     * 
     * Returns a new RawParams instance with the specified key-value pair added.
     * The original instance remains unchanged due to immutability.
     * 
     * @param string $key Parameter key to add
     * @param mixed $value Parameter value to add
     * @return self New RawParams instance with added parameter
     * 
     * @example
     * // Add parameter
     * $newParams = $params->with('timestamp', time());
     * 
     * @example
     * // Add computed values
     * $newParams = $params->with('computed_hash', md5(serialize($params->all())));
     * 
     * @example
     * // Add default values
     * $newParams = $params->with('page', $params->get('page', 1));
     * 
     * @example
     * // Chain operations
     * $newParams = $params
     *     ->with('created_at', date('Y-m-d H:i:s'))
     *     ->with('user_id', $currentUser->getId());
     */
    public function with(string $key, mixed $value): self
    {
        return new self([...$this->params, $key => $value]);
    }

    /**
     * Create new instance with merged data
     * 
     * Returns a new RawParams instance with the provided array merged
     * with existing parameters. New values overwrite existing ones.
     * 
     * @param array $data Array to merge with existing parameters
     * @return self New RawParams instance with merged data
     * 
     * @example
     * // Merge additional data
     * $newParams = $params->merge(['status' => 'active', 'verified' => true]);
     * 
     * @example
     * // Override existing values
     * $newParams = $params->merge(['page' => 1, 'limit' => 50]);
     * 
     * @example
     * // Add computed values
     * $newParams = $params->merge([
     *     'total_count' => $this->getTotalCount(),
     *     'page_count' => $this->getPageCount()
     * ]);
     * 
     * @example
     * // Merge with defaults
     * $defaults = ['page' => 1, 'limit' => 50, 'sort' => 'name'];
     * $newParams = $params->merge($defaults);
     */
    public function merge(array $data): self
    {
        return new self([...$this->params, ...$data]);
    }

    /**
     * Magic getter for parameter access
     * 
     * Provides object-style access to parameters using magic __get method.
     * Returns null if the parameter doesn't exist.
     * 
     * @param string $name Parameter name
     * @return mixed Parameter value or null
     * 
     * @example
     * // Object-style access
     * $name = $params->name;
     * $email = $params->email;
     * $age = $params->age;
     * 
     * @example
     * // In templates or views
     * echo $params->search ?? 'No search term';
     * echo $params->category ?? 'All categories';
     * 
     * @example
     * // With null coalescing
     * $status = $params->status ?? 'inactive';
     * $priority = $params->priority ?? 'normal';
     */
    public function __get(string $name): mixed
    {
        return $this->params[$name] ?? null;
    }

    /**
     * Magic isset for parameter checking
     * 
     * Provides object-style parameter existence checking using isset().
     * Works with the magic __get method for consistent object interface.
     * 
     * @param string $name Parameter name
     * @return bool True if parameter exists and is not null
     * 
     * @example
     * // Object-style checking
     * if (isset($params->name)) {
     *     echo "Name: " . $params->name;
     * }
     * 
     * @example
     * // In templates
     * <?php if (isset($params->search)): ?>
     *     <div>Search results for: <?= htmlspecialchars($params->search) ?></div>
     * <?php endif; ?>
     * 
     * @example
     * // Conditional processing
     * if (isset($params->debug) && $params->debug) {
     *     $this->enableDebugMode();
     * }
     */
    public function __isset(string $name): bool
    {
        return isset($this->params[$name]);
    }

    /**
     * Magic debug info for var_dump
     * 
     * Controls what information is displayed when var_dump() is used
     * on the RawParams object. Shows the underlying parameter array.
     * 
     * @return array Parameters array for debugging
     * 
     * @example
     * // Debug output
     * var_dump($params);
     * // Shows: array(3) { ["name"]=> string(4) "John" ["age"]=> int(30) ["status"]=> string(6) "active" }
     * 
     * @example
     * // In debugging
     * error_log(print_r($params, true));
     * 
     * @example
     * // Development tools
     * $debugInfo = $params->__debugInfo();
     * var_dump($debugInfo);
     */
    public function __debugInfo(): array
    {
        return $this->params;
    }

    /**
     * Filter parameters by allowed keys
     * 
     * Returns a new RawParams instance containing only the parameters
     * with keys that are in the allowed keys list. Throws exception
     * if unknown parameters are found.
     * 
     * @param array $allowedKeys List of allowed parameter keys
     * @return self New RawParams instance with filtered parameters
     * @throws BadRequestException If unknown parameters are found
     * 
     * @example
     * // Filter by allowed keys
     * $allowedKeys = ['name', 'email', 'status'];
     * $filteredParams = $params->onlyAllowed($allowedKeys);
     * 
     * @example
     * // In controller validation
     * $allowedKeys = ['search', 'category', 'page', 'limit'];
     * $params = new RawParams($request->all())->onlyAllowed($allowedKeys);
     * 
     * @example
     * // API endpoint validation
     * $allowedKeys = ['title', 'content', 'published'];
     * try {
     *     $params = $params->onlyAllowed($allowedKeys);
     * } catch (BadRequestException $e) {
     *     return $this->errorResponse($e);
     * }
     * 
     * @example
     * // Form validation
     * $allowedFields = ['name', 'email', 'password', 'confirm_password'];
     * $formData = new RawParams($request->getParsedBody());
     * $validData = $formData->onlyAllowed($allowedFields);
     */
    public function onlyAllowed(array $allowedKeys): self
    {
        $unknown = array_diff(array_keys($this->params), $allowedKeys);

        if ($unknown !== []) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'request.unknown_parameters',
                    domain: 'validation',
                    params: [
                        'unknown_keys' => implode(', ', $unknown),
                        'allowed_keys' => implode(', ', $allowedKeys),
                    ]
                )
            );
        }

        $filtered = array_intersect_key($this->params, array_flip($allowedKeys));
        return new self($filtered);
    }

    /**
     * Sanitize parameters for security
     * 
     * Returns a new RawParams instance with all parameters sanitized
     * using the InputSanitizer utility. Helps prevent XSS and other
     * security vulnerabilities.
     * 
     * @return self New RawParams instance with sanitized parameters
     * 
     * @example
     * // Sanitize user input
     * $cleanParams = $params->sanitize();
     * $data = $cleanParams->all();
     * 
     * @example
     * // In middleware
     * $rawParams = new RawParams($request->getParsedBody());
     * $sanitizedParams = $rawParams->sanitize();
     * $request = $request->withParsedBody($sanitizedParams->all());
     * 
     * @example
     * // Form processing
     * $formData = new RawParams($request->getParsedBody());
     * $cleanData = $formData->sanitize()->all();
     * $this->processForm($cleanData);
     * 
     * @example
     * // API input validation
     * $apiData = new RawParams($request->getParsedBody());
     * $sanitizedData = $apiData->sanitize();
     * $this->validateAndProcess($sanitizedData->all());
     */
    public function sanitize(): self
    {
        return new self(InputSanitizer::process($this->params));
    }
}
