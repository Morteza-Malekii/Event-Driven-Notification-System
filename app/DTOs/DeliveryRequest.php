<?php

namespace App\DTOs;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;

final readonly class DeliveryRequest
{
    public function __construct(
        public string $notificationId,
        public NotificationChannel $channel,
        public string $recipient,
        public string $content,
        public array $metadata,
        public NotificationPriority $priority,
    ) {}
}
