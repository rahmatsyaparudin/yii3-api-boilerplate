<?php

declare(strict_types=1);

return [
    // Generic HTTP / API
    'bad_request' => 'Bad request',
    'unauthorized' => 'Unauthorized Access',
    'forbidden' => 'Forbidden Access Occurred',
    'not_found' => 'Not found',
    'method_not_allowed' => 'Method not allowed',
    'unsupported_media_type' => 'Unsupported media type',
    'too_many_requests' => 'Too many requests',
    'internal_error' => 'Internal server error',
    'service_unavailable' => 'Service unavailable',

    // Authentication / Authorization
    'auth.authorization_header_missing' => 'Authorization header missing',
    'auth.invalid_token' => 'Invalid or expired token',
    'auth.invalid_issuer' => 'Invalid token issuer',
    'auth.invalid_audience' => 'Invalid token audience',
    'auth.missing_claim' => 'Token missing required claim: {claim}',

    // Request / Payload
    'request.invalid_json' => 'Invalid JSON payload',
    'request.invalid_body' => 'Invalid request body',
    'request.missing_parameter' => 'Missing parameter: {param}',
    'request.invalid_parameter' => 'Invalid parameter: {param}',
    'request.host_not_allowed' => 'Host is not allowed: {host}',
    'request.origin_not_allowed' => 'Origin is not allowed: {origin}',

    // Filtering / Sorting / Pagination
    'filter.invalid_keys' => 'Invalid field keys: {keys}',
    'filter.not_allowed' => 'Filter is not allowed: {filter}',
    'sort.invalid_field' => 'Invalid sort field: {field}',
    'sort.invalid_direction' => 'Invalid sort direction: {direction}',
    'pagination.invalid_page' => 'Invalid page value',
    'pagination.invalid_page_size' => 'Invalid page size value',

    // Data type / format (generic, usable outside validator)
    'type.string' => '{field} must be a string',
    'type.integer' => '{field} must be an integer',
    'type.numeric' => '{field} must be a number',
    'type.boolean' => '{field} must be a boolean',
    'type.array' => '{field} must be an array',
    'type.object' => '{field} must be an object',

    'format.email' => '{field} must be a valid email address',
    'format.url' => '{field} must be a valid URL',
    'format.uuid' => '{field} must be a valid UUID',
    'format.date' => '{field} must be a valid date',
    'format.datetime' => '{field} must be a valid datetime',
    'format.json' => '{field} must be a valid JSON',

    // Range / length
    'range.min' => '{field} must be at least {min}',
    'range.max' => '{field} must not exceed {max}',
    'length.min' => '{field} must be at least {min} characters',
    'length.max' => '{field} must not exceed {max} characters',

    // File / upload
    'file.invalid' => 'Invalid file',
    'file.too_large' => 'File is too large',
    'file.invalid_type' => 'Invalid file type',

    // Resource / DB
    'resource.not_found' => '{resource} not found',
    'resource.conflict' => '{resource} conflict',
    'resource.already_exists' => '{resource} already exists',

    // Misc
    'operation.not_allowed' => 'Operation not allowed',

    'access.denied' => 'Access denied',
    'access.insufficient_permissions' => 'You do not have sufficient permissions to perform this action',
    'access.auth_required' => 'Authentication is required to access this resource',

    'rate_limit.exceeded' => 'Too many requests. Please try again in {seconds} seconds.',
    'rate_limit.try_again' => 'Please try again in {seconds} seconds.',

];
