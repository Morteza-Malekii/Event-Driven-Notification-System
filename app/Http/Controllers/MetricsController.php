<?php

namespace App\Http\Controllers;

use App\Enums\NotificationPriority;
use App\Services\MetricsService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;

class MetricsController extends Controller
{
    /**
     * @OA\Get(
     *     path="/metrics",
     *     tags={"Observability"},
     *     summary="Real-time metrics",
     *     description="Returns queue depths, sent/failed counts, and average latency per channel.",
     *     @OA\Response(
     *         response=200,
     *         description="Metrics snapshot",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="notifications", type="object",
     *                     @OA\Property(property="sent",   type="integer"),
     *                     @OA\Property(property="failed", type="integer")
     *                 ),
     *                 @OA\Property(property="channels", type="object",
     *                     @OA\Property(property="sms", type="object",
     *                         @OA\Property(property="sent",           type="integer"),
     *                         @OA\Property(property="failed",         type="integer"),
     *                         @OA\Property(property="avg_latency_ms", type="number", nullable=true),
     *                         @OA\Property(property="sample_count",   type="integer")
     *                     )
     *                 ),
     *                 @OA\Property(property="queues", type="object"),
     *                 @OA\Property(property="generated_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
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
