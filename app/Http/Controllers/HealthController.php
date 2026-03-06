<?php

namespace App\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

class HealthController extends Controller
{
    /**
     * @OA\Get(
     *     path="/health",
     *     tags={"Observability"},
     *     summary="Health check",
     *     description="Returns the health status of database, Redis, and cache services.",
     *     @OA\Response(
     *         response=200,
     *         description="All services healthy",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="status", type="string", enum={"healthy","degraded"}),
     *                 @OA\Property(property="services", type="object",
     *                     @OA\Property(property="database", type="object", @OA\Property(property="status", type="string")),
     *                     @OA\Property(property="redis",    type="object", @OA\Property(property="status", type="string")),
     *                     @OA\Property(property="cache",    type="object", @OA\Property(property="status", type="string"))
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=503, description="One or more services degraded")
     * )
     */
    public function __invoke(): JsonResponse
    {
        $services = [
            'database' => $this->checkDatabase(),
            'redis'    => $this->checkRedis(),
            'cache'    => $this->checkCache(),
        ];

        $healthy = collect($services)->every(fn($c) => $c['status'] === 'ok');

        return ApiResponse::success([
            'status'   => $healthy ? 'healthy' : 'degraded',
            'services' => $services,
        ], $healthy ? 200 : 503);
    }

    private function checkDatabase(): array
    {
        try {
            DB::selectOne('SELECT 1');
            return ['status' => 'ok'];
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkRedis(): array
    {
        try {
            Redis::ping();
            return ['status' => 'ok'];
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkCache(): array
    {
        try {
            Cache::put('health_check', true, 5);
            Cache::get('health_check');
            return ['status' => 'ok'];
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
