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
    /**
     * @OA\Post(
     *     path="/notifications",
     *     tags={"Notifications"},
     *     summary="Create a notification",
     *     description="Creates a single notification and dispatches it to the queue. Supports idempotency via idempotency_key field.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"channel","recipient","content"},
     *             @OA\Property(property="channel",         type="string", enum={"sms","email","push"}, example="sms"),
     *             @OA\Property(property="recipient",       type="string", example="+905551234567"),
     *             @OA\Property(property="content",         type="string", example="Your OTP is 1234"),
     *             @OA\Property(property="priority",        type="string", enum={"high","normal","low"}, example="normal"),
     *             @OA\Property(property="metadata",        type="object"),
     *             @OA\Property(property="scheduled_at",    type="string", format="date-time", nullable=true),
     *             @OA\Property(property="idempotency_key", type="string", example="order-456-sms"),
     *             @OA\Property(property="max_attempts",    type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Notification created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/NotificationResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Idempotency replay — returning existing notification",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
     *     ),
     *     @OA\Response(response=409, description="Idempotency key conflict", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(
        CreateNotificationRequest $request,
        CreateNotificationAction $action,
    ): JsonResponse {
        try {
            $data = array_filter(array_merge($request->validated(), [
                'correlation_id' => $request->attributes->get('correlation_id'),
            ]), fn($v) => $v !== null);

            $notification = $action->execute($data);

            $status = $notification->wasRecentlyCreated ? 201 : 200;

            return ApiResponse::success(new NotificationResource($notification), $status);
        } catch (DuplicateIdempotencyKeyException $e) {
            return ApiResponse::error('DUPLICATE_IDEMPOTENCY_KEY', $e->getMessage(), 409);
        }
    }

    /**
     * @OA\Get(
     *     path="/notifications/{id}",
     *     tags={"Notifications"},
     *     summary="Get a notification",
     *     description="Returns a notification by UUID including all delivery attempts.",
     *     @OA\Parameter(name="id", in="path", required=true, description="Notification UUID", @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(
     *         response=200,
     *         description="Notification found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/NotificationResource")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $notification = Notification::with('deliveryAttempts')->findOrFail($id);

        return ApiResponse::success(new NotificationResource($notification));
    }

    /**
     * @OA\Get(
     *     path="/notifications",
     *     tags={"Notifications"},
     *     summary="List notifications",
     *     description="Returns a paginated list of notifications with optional filters.",
     *     @OA\Parameter(name="status",   in="query", @OA\Schema(type="string", enum={"pending","scheduled","queued","processing","sent","failed","canceled"})),
     *     @OA\Parameter(name="channel",  in="query", @OA\Schema(type="string", enum={"sms","email","push"})),
     *     @OA\Parameter(name="priority", in="query", @OA\Schema(type="string", enum={"high","normal","low"})),
     *     @OA\Parameter(name="from",     in="query", description="Start date (Y-m-d)", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="to",       in="query", description="End date (Y-m-d)",   @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=20)),
     *     @OA\Response(
     *         response=200,
     *         description="Paginated list",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/NotificationResource")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="total",        type="integer"),
     *                 @OA\Property(property="per_page",     type="integer"),
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="last_page",    type="integer")
     *             )
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/notifications/{id}/cancel",
     *     tags={"Notifications"},
     *     summary="Cancel a notification",
     *     description="Cancels a pending or queued notification. Sent or failed notifications cannot be canceled.",
     *     @OA\Parameter(name="id", in="path", required=true, description="Notification UUID", @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(
     *         response=200,
     *         description="Notification canceled",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/NotificationResource")
     *         )
     *     ),
     *     @OA\Response(response=409, description="Cannot cancel in current state", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
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
