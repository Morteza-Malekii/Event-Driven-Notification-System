<?php

namespace App\Listeners;

use App\Events\NotificationCreated;
use App\Events\NotificationDispatched;
use App\Jobs\ProcessNotificationJob;
use Illuminate\Support\Facades\DB;

class DispatchNotificationJobListener
{
    public function handle(NotificationCreated $event): void
    {
        $notification = $event->notification;

        if ($notification->scheduled_at && $notification->scheduled_at->isFuture()) {
            $notification->markAsScheduled();
            return;
        }

        DB::transaction(function () use ($notification) {
            $notification->markAsQueued();

            ProcessNotificationJob::dispatch($notification->id)
                ->onQueue($notification->priority->queueName())
                ->afterCommit();
        });

        event(new NotificationDispatched($notification));
    }
}
