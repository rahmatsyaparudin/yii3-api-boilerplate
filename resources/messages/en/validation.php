<?php

declare(strict_types=1);

/**
 * Validation messages (English)
 * Complete validation rules for common use cases
 */
return [
    // Required
    'required' => 'The {resource} field is required',
    'required.if' => 'The {resource} field is required when {other} is {value}',
    'required.unless' => 'The {resource} field is required unless {other} is {value}',
    'required.with' => 'The {resource} field is required when {other} is present',
    'required.without' => 'The {resource} field is required when {other} is not present',
    'filled' => 'The {resource} field must have a value',
    'present' => 'The {resource} field must be present',
    
    // String Prefix
    'string.invalid' => 'The {field} field must be a string',
    'string.min' => 'The {field} field must be at least {min} characters',
    'string.max' => 'The {field} field must not exceed {max} characters',
    'string.length' => 'The {field} field must be exactly {length} characters',
    'string.between' => 'The {field} field must be between {min} and {max} characters',
    'string.alpha' => 'The {field} field may only contain letters',
    'string.alpha_num' => 'The {field} field may only contain letters and numbers',
    'string.alpha_dash' => 'The {field} field may only contain letters, numbers, dashes, and underscores',
    'string.starts_with' => 'The {field} field must start with: {values}',
    'string.ends_with' => 'The {field} field must end with: {values}',
    'string.lowercase' => 'The {field} field must be lowercase',
    'string.uppercase' => 'The {field} field must be uppercase',
    
    // Number Prefix
    'number.invalid' => 'The {field} field must be a number',
    'number.integer' => 'The {field} field must be an integer',
    'number.decimal' => 'The {field} field must be a decimal number',
    'number.min' => 'The {field} field must be at least {min}',
    'number.max' => 'The {field} field must not exceed {max}',
    'number.between' => 'The {field} field must be between {min} and {max}',
    'number.positive' => 'The {field} field must be a positive number',
    'number.negative' => 'The {field} field must be a negative number',
    'number.divisible_by' => 'The {field} field must be divisible by {divisor}',
    
    // Format
    'format.email' => 'The {field} field must be a valid email address',
    'format.url' => 'The {field} field must be a valid URL',
    'format.ip' => 'The {field} field must be a valid IP address',
    'format.ipv4' => 'The {field} field must be a valid IPv4 address',
    'format.ipv6' => 'The {field} field must be a valid IPv6 address',
    'format.mac_address' => 'The {field} field must be a valid MAC address',
    'format.uuid' => 'The {field} field must be a valid UUID',
    'format.json' => 'The {field} field must be a valid JSON string',
    'format.regex' => 'The {field} field format is invalid',
    
    // Date & Time
    'date.invalid' => 'The {field} field must be a valid date',
    'date.format' => 'The {field} field must match the format {format}',
    'date.before' => 'The {field} field must be a date before {date}',
    'date.after' => 'The {field} field must be a date after {date}',
    'date.before_or_equal' => 'The {field} field must be a date before or equal to {date}',
    'date.after_or_equal' => 'The {field} field must be a date after or equal to {date}',
    'date.timezone' => 'The {field} field must be a valid timezone',
    
    // File Prefix
    'file.invalid' => 'The {resource} must be a valid file',
    'file.mimes' => 'The {resource} must be a file of type: {types}',
    'file.mimetypes' => 'The {resource} must be a file of type: {types}',
    'file.max_size' => 'The {resource} must not exceed {size} KB',
    'file.min_size' => 'The {resource} must be at least {size} KB',
    
    // Image Prefix
    'image.invalid' => 'The {resource} must be an image',
    'image.dimensions' => 'The {resource} has invalid image dimensions',
    'image.min_width' => 'The {resource} must be at least {width} pixels wide',
    'image.max_width' => 'The {resource} must not exceed {width} pixels wide',
    'image.min_height' => 'The {resource} must be at least {height} pixels high',
    'image.max_height' => 'The {resource} must not exceed {height} pixels high',
    
    // Comparison Prefix
    'compare.same' => 'The {resource} must match {other}',
    'compare.different' => 'The {resource} must be different from {other}',
    'compare.confirmed' => 'The {resource} confirmation does not match',
    'compare.in' => 'The selected {resource} is invalid',
    'compare.not_in' => 'The selected {resource} is invalid',
    
    // Boolean Prefix
    'boolean.invalid' => 'The {resource} field must be true or false',
    'boolean.accepted' => 'The {resource} must be accepted',
    'boolean.declined' => 'The {resource} must be declined',
    
    // Array Prefix
    'array.invalid' => 'The {resource} must be an array',
    'array.min' => 'The {resource} must have at least {min} items',
    'array.max' => 'The {resource} must not have more than {max} items',
    'array.between' => 'The {resource} must have between {min} and {max} items',
    'array.distinct' => 'The {resource} field has duplicate values',
    
    // Existence Prefix
    'exists.invalid' => 'The selected {resource} does not exist',
    'exists.unique' => 'The {resource} has already been taken',
    'exists.already_exists' => 'The {resource} with value "{value}" already exists',
    'exists.cannot_delete_active' => 'Cannot delete {resource} while it is still active',

    // Special Prefix
    'special.nullable' => 'The {resource} field may be null',
    'special.prohibited' => 'The {resource} field is prohibited',
    'special.prohibited_if' => 'The {resource} field is prohibited when {other} is {value}',
    'special.prohibited_unless' => 'The {resource} field is prohibited unless {other} is {value}',

    // Status Prefix
    'status.forbid_update' => '{resource} data with status "{status}" cannot be updated',
    'status.invalid_transition' => 'Cannot update {resource} from status "{from}" to "{to}"',
    'status.invalid_on_creation' => '{resource} must be in "active" or "draft" status to proceed with creation',
    'status.cannot_delete' => 'Cannot delete {resource} with status "{status}"',
    'status.deletion_restricted' => '{resource} with ID {id} cannot be deleted while in "{status}" status.',

    // Restore Prefix
    'resource.restored' => '{resource} has been restored successfully',
    'resource.restore_failed' => 'Failed to restore {resource}: {error}',
    'resource.not_deleted' => '{resource} is not in deleted status',

    // Request Prefix
    'request.unknown_parameters' => 'Unknown parameters: {unknown_keys}. Allowed parameters: {allowed_keys}',

    // Input Sanitizer Prefix
    'input_sanitizer.input_structure_too_deep' => 'Input structure is too deep. Current depth is {depth}, but the maximum allowed is {max_depth}.',
    'input_sanitizer.input_too_long' => 'Input is too long. Current length is {length}, but the maximum allowed is {max_length}.',
    'input_sanitizer.invalid_encoding' => 'Input contains invalid encoding. Current encoding is {encoding}.',
    'input_sanitizer.array_too_large' => 'The array exceeds the maximum allowed size of {max_size} elements.',
    'input_sanitizer.float_overflow' => 'Value is out of range. It must be between {min_value} and {max_value}.',
    'input_sanitizer.integer_overflow' => 'Value is out of range. It must be between {min_value} and {max_value}.',
    'input_sanitizer.invalid_array_key_type' => 'The array key {key} has an invalid type. Keys must be strings or integers.',
    'input_sanitizer.invalid_array_key_length' => 'The array key is too long. Maximum allowed length for a key is {max_length} characters,',
    'input_sanitizer.invalid_array_key' => 'The array key {key} contains invalid characters or format.',

];
