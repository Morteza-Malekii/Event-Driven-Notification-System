<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Notification $notification,
        public readonly string       $errorMessage,
        public readonly bool         $isPermanent,
        public readonly int          $durationMs,
    ) {}
}
