<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\NotificationBatchController;
use App\Http\Controllers\NotificationController;
use App\Http\Middleware\CorrelationIdMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(CorrelationIdMiddleware::class)->group(function () {
    // Notifications
    Route::get('/notifications',          [NotificationController::class, 'index']);
    Route::post('/notifications',         [NotificationController::class, 'store']);
    Route::get('/notifications/{id}',     [NotificationController::class, 'show']);
    Route::post('/notifications/{id}/cancel', [NotificationController::class, 'cancel']);

    // Batches
    Route::post('/batches',               [NotificationBatchController::class, 'store']);
    Route::get('/batches/{id}',           [NotificationBatchController::class, 'show']);

    // Observability
    Route::get('/health',                 HealthController::class);
    Route::get('/metrics',                MetricsController::class);
});
