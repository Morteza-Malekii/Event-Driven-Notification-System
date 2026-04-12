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
    /**
     * @OA\Post(
     *     path="/batches",
     *     tags={"Batches"},
     *     summary="Create a batch of notifications",
     *     description="Creates up to 1000 notifications in a single request.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"notifications"},
     *             @OA\Property(property="name", type="string", example="Flash sale campaign"),
     *             @OA\Property(property="metadata", type="object"),
     *             @OA\Property(
     *                 property="notifications",
     *                 type="array",
     *                 maxItems=1000,
     *                 @OA\Items(
     *                     required={"channel","recipient","content"},
     *                     @OA\Property(property="channel",      type="string", enum={"sms","email","push"}),
     *                     @OA\Property(property="recipient",    type="string"),
     *                     @OA\Property(property="content",      type="string"),
     *                     @OA\Property(property="priority",     type="string", enum={"high","normal","low"}),
     *                     @OA\Property(property="metadata",     type="object"),
     *                     @OA\Property(property="scheduled_at", type="string", format="date-time", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Batch created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="batch",   ref="#/components/schemas/NotificationBatchResource"),
     *                 @OA\Property(property="created", type="integer"),
     *                 @OA\Property(property="failed",  type="integer"),
     *                 @OA\Property(property="errors",  type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(
        CreateBatchNotificationRequest $request,
        CreateBatchAction $action,
    ): JsonResponse {
        $result = $action->execute(array_merge($request->validated(), [
            'correlation_id' => $request->attributes->get('correlation_id'),
        ]));

        return ApiResponse::success([
            'batch'   => new NotificationBatchResource($result['batch']),
            'created' => $result['created'],
            'failed'  => $result['failed'],
            'errors'  => $result['errors'],
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/batches/{id}",
     *     tags={"Batches"},
     *     summary="Get a batch",
     *     description="Returns the batch status and counters by UUID.",
     *     @OA\Parameter(name="id", in="path", required=true, description="Batch UUID", @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(
     *         response=200,
     *         description="Batch found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/NotificationBatchResource")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $batch = NotificationBatch::findOrFail($id);

        return ApiResponse::success(new NotificationBatchResource($batch));
    }
}
