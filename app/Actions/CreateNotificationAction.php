<?php

namespace App\Actions;

use App\Events\NotificationCreated;
use App\Models\Notification;
use App\Services\IdempotencyService;
use Illuminate\Support\Facades\DB;

class CreateNotificationAction
{
    public function __construct(
        private readonly IdempotencyService $idempotency,
    ) {}

    public function execute(array $data): Notification
    {
        $key  = $data['idempotency_key'] ?? null;
        $hash = null;

        if ($key) {
            $hashData = array_diff_key($data, array_flip(['idempotency_key', 'correlation_id', 'batch_id']));
            $hash     = $this->idempotency->hashRequest($hashData);
            $cached   = $this->idempotency->check($key, $hash);

            if ($cached) {
                return Notification::find($cached['notification_id']);
            }
        }

        $notification = DB::transaction(
            fn () => Notification::create($data)
        );

        if ($key) {
            $this->idempotency->store($key, $hash, $notification->id, [
                'notification_id' => $notification->id,
            ]);
        }

        event(new NotificationCreated($notification));

        return $notification;
    }
}
