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
            $hash   = $this->idempotency->hashRequest($data);
            $cached = $this->idempotency->check($key, $hash);

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
