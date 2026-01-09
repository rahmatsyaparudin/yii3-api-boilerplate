<?php

declare(strict_types=1);

/**
 * Validation messages (English)
 * Complete validation rules for common use cases
 */
return [
    // Required
    'required' => '{field} is required',
    'required_if' => '{field} is required when {other} is {value}',
    'required_unless' => '{field} is required unless {other} is {value}',
    'required_with' => '{field} is required when {other} is present',
    'required_without' => '{field} is required when {other} is not present',
    'filled' => '{field} must have a value when present',
    'present' => '{field} field must be present',
    
    // String
    'string' => '{field} must be a string',
    'min_length' => '{field} must be at least {min} characters',
    'max_length' => '{field} must not exceed {max} characters',
    'length' => '{field} must be exactly {length} characters',
    'length_between' => '{field} must be between {min} and {max} characters',
    'alpha' => '{field} may only contain letters',
    'alpha_num' => '{field} may only contain letters and numbers',
    'alpha_dash' => '{field} may only contain letters, numbers, dashes and underscores',
    'starts_with' => '{field} must start with: {values}',
    'ends_with' => '{field} must end with: {values}',
    'lowercase' => '{field} must be lowercase',
    'uppercase' => '{field} must be uppercase',
    
    // Numeric
    'numeric' => '{field} must be a number',
    'integer' => '{field} must be an integer',
    'decimal' => '{field} must be a decimal number',
    'min_value' => '{field} must be at least {min}',
    'max_value' => '{field} must not exceed {max}',
    'between' => '{field} must be between {min} and {max}',
    'positive' => '{field} must be a positive number',
    'negative' => '{field} must be a negative number',
    'divisible_by' => '{field} must be divisible by {divisor}',
    
    // Format
    'email' => '{field} must be a valid email address',
    'url' => '{field} must be a valid URL',
    'ip' => '{field} must be a valid IP address',
    'ipv4' => '{field} must be a valid IPv4 address',
    'ipv6' => '{field} must be a valid IPv6 address',
    'mac_address' => '{field} must be a valid MAC address',
    'uuid' => '{field} must be a valid UUID',
    'json' => '{field} must be a valid JSON string',
    'regex' => '{field} format is invalid',
    
    // Date & Time
    'date' => '{field} must be a valid date',
    'date_format' => '{field} must match the format {format}',
    'before' => '{field} must be a date before {date}',
    'after' => '{field} must be a date after {date}',
    'before_or_equal' => '{field} must be a date before or equal to {date}',
    'after_or_equal' => '{field} must be a date after or equal to {date}',
    'timezone' => '{field} must be a valid timezone',
    
    // File & Upload
    'file' => '{field} must be a file',
    'image' => '{field} must be an image',
    'mimes' => '{field} must be a file of type: {types}',
    'mimetypes' => '{field} must be a file of type: {types}',
    'file_size' => '{field} must not exceed {size} KB',
    'file_min_size' => '{field} must be at least {size} KB',
    'dimensions' => '{field} has invalid image dimensions',
    'min_width' => '{field} must be at least {width} pixels wide',
    'max_width' => '{field} must not exceed {width} pixels wide',
    'min_height' => '{field} must be at least {height} pixels high',
    'max_height' => '{field} must not exceed {height} pixels high',
    
    // Comparison
    'same' => '{field} must match {other}',
    'different' => '{field} must be different from {other}',
    'confirmed' => '{field} confirmation does not match',
    'in' => 'Selected {field} is invalid',
    'not_in' => 'Selected {field} is invalid',
    
    // Boolean
    'boolean' => '{field} must be true or false',
    'accepted' => '{field} must be accepted',
    'declined' => '{field} must be declined',
    
    // Array
    'array' => '{field} must be an array',
    'array_min' => '{field} must have at least {min} items',
    'array_max' => '{field} must not have more than {max} items',
    'array_between' => '{field} must have between {min} and {max} items',
    'distinct' => '{field} has duplicate values',
    
    // Existence
    'exists' => 'Selected {field} is invalid',
    'unique' => '{field} has already been taken',
    
    // Special
    'nullable' => '{field} may be null',
    'prohibited' => '{field} field is prohibited',
    'prohibited_if' => '{field} is prohibited when {other} is {value}',
    'prohibited_unless' => '{field} is prohibited unless {other} is {value}',
];
