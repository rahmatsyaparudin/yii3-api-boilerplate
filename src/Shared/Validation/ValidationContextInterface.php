<?php

declare(strict_types=1);

namespace App\Shared\Validation;

interface ValidationContextInterface
{
    public const SEARCH = 'search';
    public const CREATE = 'create';
    public const UPDATE = 'update';
    public const DELETE = 'delete';
    public const APPROVE = 'approve';
    public const REJECT = 'reject';
}
