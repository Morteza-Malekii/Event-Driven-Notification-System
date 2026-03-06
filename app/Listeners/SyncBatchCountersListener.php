<?php

namespace App\Listeners;

use App\Events\BatchCompleted;
use App\Events\NotificationCanceled;
use App\Events\NotificationFailed;
use App\Events\NotificationSent;
use App\Models\NotificationBatch;

class SyncBatchCountersListener
{
    public function handle(NotificationSent|NotificationFailed|NotificationCanceled $event): void
    {
        $notification = $event->notification;

        if (! $notification->batch_id) {
            return;
        }

        $batch = NotificationBatch::find($notification->batch_id);

        if (! $batch) {
            return;
        }

        match (true) {
            $event instanceof NotificationSent => (function () use ($batch) {
                $batch->incrementCounter('sent_count');
                $batch->decrementCounter('pending_count');
            })(),

            $event instanceof NotificationFailed => (function () use ($batch) {
                $batch->incrementCounter('failed_count');
                $batch->decrementCounter('pending_count');
            })(),

            $event instanceof NotificationCanceled => (function () use ($batch) {
                $batch->incrementCounter('canceled_count');
                $batch->decrementCounter('pending_count');
            })(),
        };

        $batch->recalculateStatus();

        if ($batch->fresh()->status->isTerminal()) {
            event(new BatchCompleted($batch));
        }
    }
}
