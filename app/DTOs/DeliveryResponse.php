<?php

namespace App\DTOs;

class DeliveryResponse
{
    public function __construct(
        public readonly string $messageId,
        public readonly string $status,
        public readonly string $sentAt,
        public readonly string $provider,
        public readonly array  $metadata = [],
    ) {}
}
