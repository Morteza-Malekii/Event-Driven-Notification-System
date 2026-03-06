<?php

namespace App\Http\Controllers;

use App\Enums\NotificationPriority;
use App\Services\MetricsService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;

class MetricsController extends Controller
{
    public function __invoke(MetricsService $metrics): JsonResponse
    {
        $channels = $metrics->getSnapshot();

        $notifications = [
            'sent'   => collect($channels)->sum('sent'),
            'failed' => collect($channels)->sum('failed'),
        ];

        $queues = collect(NotificationPriority::cases())
            ->mapWithKeys(fn($p) => [
                $p->queueName() => ['depth' => (int) Redis::llen("queues:{$p->queueName()}")],
            ])
            ->all();

        return ApiResponse::success([
            'notifications' => $notifications,
            'channels'      => $channels,
            'queues'        => $queues,
            'generated_at'  => now()->toIso8601String(),
        ]);
    }
}
