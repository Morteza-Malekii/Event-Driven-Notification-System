<?php

namespace App\Exceptions;

use RuntimeException;

class DuplicateIdempotencyKeyException extends RuntimeException
{
    public function __construct(string $key)
    {
        parent::__construct("Idempotency key '{$key}' already exists with a different request hash.");
    }
}
