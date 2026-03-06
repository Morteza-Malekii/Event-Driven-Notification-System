<?php

namespace App\Actions;

use App\Events\NotificationCanceled;
use App\Exceptions\CancellationNotAllowedException;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;

class CancelNotificationAction
{
    public function execute(string $id): Notification
    {
        return DB::transaction(function () use ($id) {
            $notification = Notification::lockForUpdate()->findOrFail($id);

            if (! $notification->isCancelable()) {
                throw new CancellationNotAllowedException(
                    "Notification [{$id}] cannot be canceled in status [{$notification->status->value}]."
                );
            }

            $notification->markAsCanceled();

            event(new NotificationCanceled($notification));

            return $notification;
        });
    }
}
