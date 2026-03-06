<?php

namespace App\Jobs;

use App\Enums\NotificationStatus;
use App\Events\NotificationCreated;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DispatchScheduledNotificationsJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Notification::where('status', NotificationStatus::SCHEDULED)
            ->where('scheduled_at', '<=', now())
            ->each(function ($notification) {
                event(new NotificationCreated($notification));
                // DispatchNotificationJobListener handles dispatching to queue
            });
    }
}
