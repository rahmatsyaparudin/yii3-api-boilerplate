<?php

declare(strict_types=1);

return [
    // Generic HTTP / API
    'bad_request' => 'The request is invalid or malformed.',
    'unauthorized' => 'Authentication is required or has failed.',
    'forbidden' => 'You do not have permission to access this resource.',
    'not_found' => 'The requested resource was not found.',
    'method_not_allowed' => 'The HTTP method used is not allowed for this endpoint.',
    'unsupported_media_type' => 'The request media type is not supported.',
    'too_many_requests' => 'Too many requests have been sent in a short period of time.',
    'internal_error' => 'An internal server error occurred.',
    'service_unavailable' => 'The service is temporarily unavailable.',

    // Authentication / Authorization
    'auth.authorization_header_missing' => 'The Authorization header is missing.',
    'auth.invalid_token' => 'The access token is invalid or has expired.',
    'auth.invalid_issuer' => 'The token issuer is not valid.',
    'auth.invalid_audience' => 'The token audience is not valid.',
    'auth.missing_claim' => 'The token is missing a required claim: {claim}.',

    // Request / Payload
    'request.invalid_json' => 'The request body contains invalid JSON.',
    'request.invalid_body' => 'The request body is invalid or improperly formatted.',
    'request.missing_parameter' => 'The required parameter is missing: {param}.',
    'request.invalid_parameter' => 'The parameter value is invalid: {param}.',
    'request.host_not_allowed' => 'The request host is not allowed: {host}.',
    'request.origin_not_allowed' => 'The request origin is not allowed: {origin}.',

    // Filtering / Sorting / Pagination
    'filter.invalid_keys' => 'The request contains unsupported field(s): {keys}.',
    'filter.not_allowed' => 'Filtering by this field is not allowed: {filter}.',
    'sort.invalid_field' => 'The specified sort field is invalid: {field}.',
    'sort.invalid_direction' => 'The specified sort direction is invalid: {direction}.',
    'pagination.invalid_page' => 'The page value must be a valid number.',
    'pagination.invalid_page_size' => 'The page size value must be valid.',

    // Route
    'route.field_required' => 'Invalid request. {resource} {field} is required in the URL.',

    // Validation
    'validation.failed' => 'Validation failed. Please review the provided data.',

    // Data type / format (generic, reusable)
    'type.string' => '{field} must be a string.',
    'type.integer' => '{field} must be an integer.',
    'type.numeric' => '{field} must be a numeric value.',
    'type.boolean' => '{field} must be a boolean value.',
    'type.array' => '{field} must be an array.',
    'type.object' => '{field} must be an object.',

    'format.email' => '{field} must be a valid email address.',
    'format.url' => '{field} must be a valid URL.',
    'format.uuid' => '{field} must be a valid UUID.',
    'format.date' => '{field} must be a valid date.',
    'format.datetime' => '{field} must be a valid date and time.',
    'format.json' => '{field} must contain valid JSON.',

    // Range / length
    'range.min' => '{field} must be greater than or equal to {min}.',
    'range.max' => '{field} must be less than or equal to {max}.',
    'length.min' => '{field} must contain at least {min} characters.',
    'length.max' => '{field} must not exceed {max} characters.',

    // File / upload
    'file.invalid' => 'The uploaded file is invalid.',
    'file.too_large' => 'The uploaded file exceeds the maximum allowed size.',
    'file.invalid_type' => 'The uploaded file type is not allowed.',

    // Resource
    'resource.not_found' => '{resource} data with {field}: {value} was not found.',
    'resource.conflict' => 'A conflict occurred with the {resource}.',
    'resource.already_exists' => 'The {resource} already exists.',
    'resource.cannot_update' => 'Cannot update {resource}. Status change from "{current_status}" to "{status}" is not allowed.',
    'resource.update_not_allowed_by_status' => 'Data changes are not allowed for {resource} when its status is "{current_status}".',
    'resource.status_already_set' => 'Cannot update {resource}. The status is already "{current_status}".',

    // Misc
    'operation.not_allowed' => 'This operation is not allowed.',

    'access.denied' => 'Access to this resource is denied.',
    'access.insufficient_permissions' => 'You do not have sufficient permissions to perform this action.',
    'access.auth_required' => 'Authentication is required to access this resource.',

    'rate_limit.exceeded' => 'Too many requests. Please try again after {seconds} seconds.',
    'rate_limit.try_again' => 'Please try again in {seconds} seconds.',

    'brand.name_already_exists' => 'Brand dengan nama "{name}" sudah ada.',
    'brand.name_min_length' => 'Nama brand harus minimal 3 karakter.',
    'brand.cannot_delete_active' => 'Tidak dapat menghapus brand yang aktif.',
];
