<?php

declare(strict_types=1);

return [
    // HTTP Prefix (Generic API Errors)
    'http.bad_request' => 'The request is invalid or malformed',
    'http.unauthorized' => 'Authentication is required or has failed',
    'http.forbidden' => 'You do not have permission to access this resource',
    'http.not_found' => 'The requested resource was not found',
    'http.method_not_allowed' => 'The HTTP method is not allowed for this endpoint',
    'http.unsupported_media_type' => 'The requested media type is not supported',
    'http.too_many_requests' => 'Too many requests. Please try again later',
    'http.internal_error' => 'An internal server error occurred',
    'http.service_unavailable' => 'The service is temporarily unavailable',
    'http.missing_request_params' => 'Missing request parameters.',

    // Security Prefix
    'security.host_not_allowed' => 'Access denied: Host not allowed',

    // Auth Prefix (Infrastructure Level)
    'auth.header_missing' => 'Authorization header is missing',
    'auth.invalid_token' => 'Access token is invalid or has expired',
    'auth.invalid_issuer' => 'The token issuer is invalid',
    'auth.invalid_audience' => 'The token audience is invalid',
    'auth.missing_claim' => 'The token is missing a required claim: {claim}',

    // Request Prefix
    'request.invalid_json' => 'The request body contains invalid JSON',
    'request.invalid_body' => 'The request body is invalid or malformed',
    'request.missing_parameter' => 'Required parameter is missing: {param}',
    'request.invalid_parameter' => 'The parameter value is invalid: {param}',
    'request.host_not_allowed' => 'The host is not allowed: {host}',
    'request.origin_not_allowed' => 'The origin is not allowed: {origin}',

    // Filtering
    'filter.invalid_keys' => 'The request contains unsupported filter field(s): {keys}',
    'filter.not_allowed' => 'Filtering by the field "{filter}" is not allowed',
    
    // Sorting
    'sort.invalid_field' => 'The specified sort field "{field}" is invalid or not allowed',
    'sort.invalid_direction' => 'The sort direction "{direction}" is invalid. Use "asc" or "desc"',
    
    // Pagination
    'pagination.invalid_page' => 'The page number must be a valid positive integer',
    'pagination.invalid_limit' => 'The page size value is invalid',
    'pagination.invalid_page_size' => 'The page size value is invalid',
    'pagination.invalid_parameter' => 'Invalid "{parameter}" parameter',
    
    // Route
    'route.field_required' => 'Invalid request. {resource} {field} is required in the URL',
    'route.parameter_missing' => '{resource} {parameter} parameter is required in the URL',

    // Validation
    'validation.failed' => 'Validation failed. Please review the provided data',

    // Data type (Prefix 'type.')
    'type.string' => 'The {field} field must be a string',
    'type.integer' => 'The {field} field must be an integer',
    'type.numeric' => 'The {field} field must be a numeric value',
    'type.boolean' => 'The {field} field must be a boolean value',
    'type.array' => 'The {field} field must be an array',
    'type.object' => 'The {field} field must be an object',

    // Format (Prefix 'format.')
    'format.email' => 'The {field} field must be a valid email address',
    'format.url' => 'The {field} field must be a valid URL',
    'format.uuid' => 'The {field} field must be a valid UUID',
    'format.date' => 'The {field} field must be a valid date',
    'format.datetime' => 'The {field} field must be a valid date and time',
    'format.json' => 'The {field} field must contain valid JSON',

    // Range & Length
    'range.min' => 'The {field} field must be greater than or equal to {min}',
    'range.max' => 'The {field} field must be less than or equal to {max}',
    'length.min' => 'The {field} field must contain at least {min} characters',
    'length.max' => 'The {field} field must not exceed {max} characters',

    // File
    'file.invalid' => 'The uploaded file is invalid',
    'file.too_large' => 'The uploaded file exceeds the maximum allowed size',
    'file.invalid_type' => 'The uploaded file type is not allowed',

    // Resource (Business Logic)
    'resource.not_found' => '{resource} data with {field}: {value} was not found',
    'resource.conflict' => 'A conflict occurred with the {resource}',
    'resource.already_exists' => 'The {resource} already exists',
    'resource.cannot_update' => 'Cannot update {resource}. Status change from "{current_status}" to "{status}" is not allowed',
    'resource.update_not_allowed_by_status' => 'Data changes are not allowed for {resource} when its status is "{current_status}"',
    'resource.status_already_set' => 'Cannot update {resource}. The status is already "{current_status}"',

    // Access & Rate Limit
    'operation.not_allowed' => 'This operation is not allowed',
    'access.denied' => 'Access to this resource is denied',
    'access.insufficient_permissions' => 'You do not have sufficient permissions to perform this action',
    'access.auth_required' => 'Authentication is required to access this resource',
    'rate_limit.exceeded' => 'Too many requests. Please try again after {seconds} seconds',
    'rate_limit.try_again' => 'Please try again in {seconds} seconds',

    'business.violation' => 'Business rule violation: {reason}',
    'business.limit_reached' => 'Limit {resource} reached',
    'business.requirement_unmet' => 'Requirement not met: {reason}',

    'service.error' => 'Failed to process request: {reason}',
    'service.unavailable' => 'Service "{service}" is unavailable',
    'service.failed' => 'The service process failed',
];
