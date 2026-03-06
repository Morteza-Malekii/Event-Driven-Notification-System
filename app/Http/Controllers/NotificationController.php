<?php

namespace App\Http\Controllers;

use App\Actions\CancelNotificationAction;
use App\Actions\CreateNotificationAction;
use App\Exceptions\CancellationNotAllowedException;
use App\Exceptions\DuplicateIdempotencyKeyException;
use App\Http\Requests\CreateNotificationRequest;
use App\Http\Requests\ListNotificationsRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function store(
        CreateNotificationRequest $request,
        CreateNotificationAction $action,
    ): JsonResponse {
        try {
            $data = array_filter(array_merge($request->validated(), [
                'correlation_id' => $request->attributes->get('correlation_id'),
            ]), fn($v) => $v !== null);

            $notification = $action->execute($data);

            return ApiResponse::success(new NotificationResource($notification), 201);
        } catch (DuplicateIdempotencyKeyException $e) {
            return ApiResponse::error('DUPLICATE_IDEMPOTENCY_KEY', $e->getMessage(), 409);
        }
    }

    public function show(string $id): JsonResponse
    {
        $notification = Notification::with('deliveryAttempts')->findOrFail($id);

        return ApiResponse::success(new NotificationResource($notification));
    }

    public function index(ListNotificationsRequest $request): JsonResponse
    {
        $query = Notification::query();

        if ($status = $request->validated('status')) {
            $query->ofStatus($status);
        }

        if ($channel = $request->validated('channel')) {
            $query->ofChannel($channel);
        }

        if ($priority = $request->validated('priority')) {
            $query->ofPriority($priority);
        }

        if ($from = $request->validated('from')) {
            $query->createdBetween($from, $request->validated('to'));
        }

        $perPage = $request->validated('per_page', 20);
        $paginator = $query->latest()->paginate($perPage);

        return ApiResponse::success(
            NotificationResource::collection($paginator->items()),
            200,
            [
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
            ],
        );
    }

    public function cancel(string $id, CancelNotificationAction $action): JsonResponse
    {
        try {
            $notification = $action->execute($id);

            return ApiResponse::success(new NotificationResource($notification));
        } catch (CancellationNotAllowedException $e) {
            return ApiResponse::error('CANCELLATION_NOT_ALLOWED', $e->getMessage(), 409);
        }
    }
}
