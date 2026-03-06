<?php

namespace App\Actions;

use App\Models\NotificationBatch;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateBatchAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotification,
    ) {}

    public function execute(array $data): array
    {
        $notifications = $data['notifications'] ?? [];

        return DB::transaction(function () use ($data, $notifications) {
            $batch = NotificationBatch::create([
                'name'          => $data['name'] ?? null,
                'metadata'      => $data['metadata'] ?? [],
                'total_count'   => count($notifications),
                'pending_count' => count($notifications),
            ]);

            $created = [];
            $failed  = [];
            $errors  = [];

            foreach ($notifications as $index => $notificationData) {
                try {
                    $notificationData['batch_id'] = $batch->id;
                    $created[] = $this->createNotification->execute($notificationData);
                } catch (Throwable $e) {
                    $failed[] = $index;
                    $errors[$index] = $e->getMessage();
                }
            }

            return [
                'batch'   => $batch,
                'created' => count($created),
                'failed'  => count($failed),
                'errors'  => $errors,
            ];
        });
    }
}
