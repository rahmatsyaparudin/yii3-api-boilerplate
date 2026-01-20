<?php

declare(strict_types=1);

/**
 * Validation messages (English)
 * Complete validation rules for common use cases
 */
return [
    // Required
    'required' => '{resource} is required',
    'required_if' => '{resource} is required when {other} is {value}',
    'required_unless' => '{resource} is required unless {other} is {value}',
    'required_with' => '{resource} is required when {other} is present',
    'required_without' => '{resource} is required when {other} is not present',
    'filled' => '{resource} must have a value when present',
    'present' => '{resource} field must be present',
    
    // String
    'string' => '{resource} must be a string',
    'min_length' => '{resource} must be at least {min} characters',
    'max_length' => '{resource} must not exceed {max} characters',
    'length' => '{resource} must be exactly {length} characters',
    'length_between' => '{resource} must be between {min} and {max} characters',
    'alpha' => '{resource} may only contain letters',
    'alpha_num' => '{resource} may only contain letters and numbers',
    'alpha_dash' => '{resource} may only contain letters, numbers, dashes and underscores',
    'starts_with' => '{resource} must start with: {values}',
    'ends_with' => '{resource} must end with: {values}',
    'lowercase' => '{resource} must be lowercase',
    'uppercase' => '{resource} must be uppercase',
    
    // Numeric
    'numeric' => '{resource} must be a number',
    'integer' => '{resource} must be an integer',
    'decimal' => '{resource} must be a decimal number',
    'min_value' => '{resource} must be at least {min}',
    'max_value' => '{resource} must not exceed {max}',
    'between' => '{resource} must be between {min} and {max}',
    'positive' => '{resource} must be a positive number',
    'negative' => '{resource} must be a negative number',
    'divisible_by' => '{resource} must be divisible by {divisor}',
    
    // Format
    'email' => '{resource} must be a valid email address',
    'url' => '{resource} must be a valid URL',
    'ip' => '{resource} must be a valid IP address',
    'ipv4' => '{resource} must be a valid IPv4 address',
    'ipv6' => '{resource} must be a valid IPv6 address',
    'mac_address' => '{resource} must be a valid MAC address',
    'uuid' => '{resource} must be a valid UUID',
    'json' => '{resource} must be a valid JSON string',
    'regex' => '{resource} format is invalid',
    
    // Date & Time
    'date' => '{resource} must be a valid date',
    'date_format' => '{resource} must match the format {format}',
    'before' => '{resource} must be a date before {date}',
    'after' => '{resource} must be a date after {date}',
    'before_or_equal' => '{resource} must be a date before or equal to {date}',
    'after_or_equal' => '{resource} must be a date after or equal to {date}',
    'timezone' => '{resource} must be a valid timezone',
    
    // File & Upload
    'file' => '{resource} must be a file',
    'image' => '{resource} must be an image',
    'mimes' => '{resource} must be a file of type: {types}',
    'mimetypes' => '{resource} must be a file of type: {types}',
    'file_size' => '{resource} must not exceed {size} KB',
    'file_min_size' => '{resource} must be at least {size} KB',
    'dimensions' => '{resource} has invalid image dimensions',
    'min_width' => '{resource} must be at least {width} pixels wide',
    'max_width' => '{resource} must not exceed {width} pixels wide',
    'min_height' => '{resource} must be at least {height} pixels high',
    'max_height' => '{resource} must not exceed {height} pixels high',
    
    // Comparison
    'same' => '{resource} must match {other}',
    'different' => '{resource} must be different from {other}',
    'confirmed' => '{resource} confirmation does not match',
    'in' => 'Selected {resource} is invalid',
    'not_in' => 'Selected {resource} is invalid',
    
    // Boolean
    'boolean' => '{resource} must be true or false',
    'accepted' => '{resource} must be accepted',
    'declined' => '{resource} must be declined',
    
    // Array
    'array' => '{resource} must be an array',
    'array_min' => '{resource} must have at least {min} items',
    'array_max' => '{resource} must not have more than {max} items',
    'array_between' => '{resource} must have between {min} and {max} items',
    'distinct' => '{resource} has duplicate values',
    
    // Existence
    'exists' => 'Selected {resource} is invalid',
    'unique' => '{resource} has already been taken',
    
    // Special
    'nullable' => '{resource} may be null',
    'prohibited' => '{resource} field is prohibited',
    'prohibited_if' => '{resource} is prohibited when {other} is {value}',
    'prohibited_unless' => '{resource} is prohibited unless {other} is {value}',

    'cannot_delete_active' => 'Cannot delete active {resource}',
    'already_exists' => '{resource} with value "{value}" already exists',

    // Field Status for Data 
    'status.forbid_update' => '{resource} data with status "{status}" cannot be updated.',
    'status.cannot_update' => 'Cannot update {resource} from status "{status}" to "{current_status}".',
    'status.invalid_on_creation' => '{resource} must be in "active" or "draft" status to proceed with creation.'
];
