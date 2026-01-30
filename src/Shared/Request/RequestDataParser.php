<?php

declare(strict_types=1);

namespace App\Shared\Request;

// PSR Interfaces
use Psr\Http\Message\ServerRequestInterface;

/**
 * Request Data Parser
 * 
 * This readonly class provides a unified interface for parsing and accessing
 * request data from both query parameters and request body. It implements
 * the DataParserInterface and handles data merging with body taking precedence.
 * 
 * @package App\Shared\Request
 * 
 * @example
 * // Basic usage in middleware
 * $parser = new RequestDataParser($request);
 * $search = $parser->get('search', '');
 * $page = $parser->get('page', 1);
 * 
 * @example
 * // Creating RequestParams from parser
 * $parser = new RequestDataParser($request);
 * $params = new RequestParams($parser);
 * 
 * @example
 * // Accessing all data
 * $parser = new RequestDataParser($request);
 * $allData = $parser->all();
 * 
 * @example
 * // In controller with named arguments
 * public function listAction(ServerRequestInterface $request): array
 * {
 *     $parser = new RequestDataParser(request: $request);
 *     $filters = $parser->get('filter', []);
 *     $pagination = $parser->get('pagination', []);
 *     return $this->service->list($filters, $pagination);
 * }
 * 
 * @example
 * // Data precedence demonstration
 * // URL: /api/users?page=1&limit=10
 * // Body: {"page": 2, "name": "John"}
 * // Result: ["page" => 2, "limit" => 10, "name" => "John"]
 */
final readonly class RequestDataParser implements DataParserInterface
{
    private array $data;

    /**
     * Request Data Parser constructor
     * 
     * Creates a new parser instance that extracts and merges data from
     * query parameters and request body, with body data taking precedence.
     * 
     * @param ServerRequestInterface $request PSR server request to parse
     * 
     * @example
     * // Basic parser creation
     * $parser = new RequestDataParser($request);
     * 
     * @example
     * // Using named arguments
     * $parser = new RequestDataParser(request: $request);
     * 
     * @example
     * // In middleware
     * public function process(ServerRequestInterface $request): ServerRequestInterface
     * {
     *     $parser = new RequestDataParser(request: $request);
     *     $params = new RequestParams(parser: $parser);
     *     return $request->withAttribute('payload', $params);
     * }
     * 
     * @example
     * // In controller constructor
     * public function __construct(
     *     private RequestDataParser $parser
     * ) {
     *     $this->data = $parser->all();
     * }
     */
    public function __construct(private ServerRequestInterface $request)
    {
        $this->data = $this->parse();
    }

    /**
     * Parse request data
     * 
     * Extracts and merges data from query parameters and request body.
     * Body data takes precedence over query parameters for overlapping keys.
     * 
     * @return array Merged request data
     * 
     * @example
     * // Query: ?page=1&limit=10
     * // Body: {"name": "John", "page": 2}
     * // Result: ["page" => 2, "limit" => 10, "name" => "John"]
     * 
     * @example
     * // JSON API request
     * // Query: ?include=profile
     * // Body: {"data": {"type": "users", "attributes": {"name": "John"}}}
     * // Result: ["include" => "profile", "data" => ["type" => "users", "attributes" => ["name" => "John"]]]
     * 
     * @example
     * // Form submission
     * // Query: ?return_url=/dashboard
     * // Body: {"name": "John", "email": "john@example.com"}
     * // Result: ["return_url" => "/dashboard", "name" => "John", "email" => "john@example.com"]
     * 
     * @example
     * // Empty body with query params
     * // Query: ?page=1&search=test
     * // Body: null or empty
     * // Result: ["page" => 1, "search" => "test"]
     */
    private function parse(): array
    {
        $query = $this->request->getQueryParams();
        $body  = $this->request->getParsedBody() ?? [];

        if (\is_object($body)) {
            $body = (array) $body;
        }

        // Body overrides query
        return \array_merge($query, $body);
    }

    /**
     * Get a specific parameter value
     * 
     * Returns the value associated with the specified key from the parsed
     * request data, or the default value if the key doesn't exist.
     * 
     * @param string $key Parameter key to retrieve
     * @param mixed $default Default value if key doesn't exist
     * @return mixed Parameter value or default
     * 
     * @example
     * // Get parameter with default
     * $search = $parser->get('search', '');
     * $page = $parser->get('page', 1);
     * $limit = $parser->get('limit', 50);
     * 
     * @example
     * // Get nested parameter
     * $filters = $parser->get('filter', []);
     * $status = $filters['status'] ?? 'active';
     * 
     * @example
     * // Type casting
     * $page = (int) $parser->get('page', 1);
     * $active = (bool) $parser->get('active', false);
     * $ids = array_filter(explode(',', $parser->get('ids', '')));
     * 
     * @example
     * // In controller
     * public function createAction(ServerRequestInterface $request): ResponseInterface
     * {
     *     $parser = new RequestDataParser(request: $request);
     *     $name = $parser->get('name');
     *     $email = $parser->get('email');
     *     return $this->service->create(['name' => $name, 'email' => $email]);
     * }
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Get all parsed data
     * 
     * Returns the complete merged array of request data from both
     * query parameters and request body.
     * 
     * @return array All parsed request data
     * 
     * @example
     * // Get all data
     * $allData = $parser->all();
     * 
     * @example
     * // For logging
     * $this->logger->info('Request data', $parser->all());
     * 
     * @example
     * // For validation
     * $data = $parser->all();
     * $this->validator->validate($data, ValidationContext::CREATE);
     * 
     * @example
     * // For caching
     * $cacheKey = md5(serialize($parser->all()));
     * 
     * @example
     * // In service layer
     * public function processRequest(ServerRequestInterface $request): array
     * {
     *     $parser = new RequestDataParser(request: $request);
     *     $data = $parser->all();
     *     return $this->processor->process($data);
     * }
     * 
     * @example
     * // Debug request data
     * $data = $parser->all();
     * var_dump($data);
     * // Shows: array(3) { ["page"]=> int(2) ["limit"]=> int(10) ["name"]=> string(4) "John" }
     */
    public function all(): array
    {
        return $this->data;
    }
}
