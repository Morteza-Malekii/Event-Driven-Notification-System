<?php

namespace App\Actions;

use App\Models\NotificationBatch;
use App\Services\IdempotencyService;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateBatchAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotification,
        private readonly IdempotencyService $idempotency,
    ) {}

    public function execute(array $data): array
    {
        $notifications = $data['notifications'] ?? [];
        $key           = $data['idempotency_key'] ?? null;
        $hash          = null;

        if ($key) {
            $hashData = array_diff_key($data, array_flip(['idempotency_key', 'correlation_id']));
            $hash     = $this->idempotency->hashRequest($hashData);
            $cached   = $this->idempotency->check($key, $hash);

            if ($cached) {
                $batch = NotificationBatch::find($cached['batch_id']);
                return [
                    'batch'      => $batch,
                    'created'    => $cached['created'],
                    'failed'     => $cached['failed'],
                    'errors'     => $cached['errors'],
                    'fromCache'  => true,
                ];
            }
        }

        $result = DB::transaction(function () use ($data, $notifications) {
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
                    $notificationData['batch_id']       = $batch->id;
                    $notificationData['correlation_id'] = $data['correlation_id'] ?? null;
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

        if ($key) {
            $this->idempotency->storeBatch($key, $hash, $result['batch']->id, [
                'batch_id' => $result['batch']->id,
                'created'  => $result['created'],
                'failed'   => $result['failed'],
                'errors'   => $result['errors'],
            ]);
        }

        return $result;
    }
}
