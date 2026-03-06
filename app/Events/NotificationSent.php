<?php

namespace App\Events;

use App\DTOs\DeliveryResponse;
use App\Models\Notification;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationSent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Notification     $notification,
        public readonly DeliveryResponse $response,
        public readonly int              $durationMs,
    ) {}
}
