<?php

namespace App\Http\Controllers;

use App\Actions\CreateBatchAction;
use App\Http\Requests\CreateBatchNotificationRequest;
use App\Http\Resources\NotificationBatchResource;
use App\Models\NotificationBatch;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class NotificationBatchController extends Controller
{
    public function store(
        CreateBatchNotificationRequest $request,
        CreateBatchAction $action,
    ): JsonResponse {
        $result = $action->execute($request->validated());

        return ApiResponse::success([
            'batch'   => new NotificationBatchResource($result['batch']),
            'created' => $result['created'],
            'failed'  => $result['failed'],
            'errors'  => $result['errors'],
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $batch = NotificationBatch::findOrFail($id);

        return ApiResponse::success(new NotificationBatchResource($batch));
    }
}
