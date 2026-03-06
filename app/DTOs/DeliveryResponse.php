<?php

namespace App\DTOs;

final readonly class DeliveryResponse
{
    public function __construct(
        public string $messageId,
        public string $status,
        public string $timestamp,
        public string $provider,
        public array  $rawResponse = [],
    ) {}
}
