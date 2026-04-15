<?php

namespace App\Exceptions;

use RuntimeException;

class ProviderDeliveryException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly bool $isPermanent,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
