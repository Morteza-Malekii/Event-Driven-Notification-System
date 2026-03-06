<?php

namespace App\Http\Controllers;

use App\Services\MetricsService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class MetricsController extends Controller
{
    public function __invoke(MetricsService $metrics): JsonResponse
    {
        return ApiResponse::success($metrics->getSnapshot());
    }
}
