<?php

namespace App\Listeners;

use App\Events\NotificationCreated;
use App\Events\NotificationDispatched;
use App\Jobs\ProcessNotificationJob;

class DispatchNotificationJobListener
{
    public function handle(NotificationCreated $event): void
    {
        $notification = $event->notification;

        if ($notification->scheduled_at && $notification->scheduled_at->isFuture()) {
            return;
        }

        $notification->markAsQueued();

        ProcessNotificationJob::dispatch($notification->id)
            ->onQueue($notification->priority->queueName());

        event(new NotificationDispatched($notification));
    }
}
