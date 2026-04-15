<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Notification System API",
 *     version="1.0.0",
 *     description="Event-driven notification system supporting SMS, Email, and Push channels with priority queues, rate limiting, retry logic, and idempotency.",
 *
 *     @OA\Contact(email="admin@example.com")
 * )
 *
 * @OA\Server(
 *     url="/api",
 *     description="API Server"
 * )
 *
 * @OA\Tag(name="Notifications", description="Create, query, and cancel notifications")
 * @OA\Tag(name="Batches",       description="Batch notification creation and status")
 * @OA\Tag(name="Observability", description="Health and metrics endpoints")
 *
 * @OA\Schema(
 *     schema="NotificationResource",
 *     type="object",
 *
 *     @OA\Property(property="id",                  type="string", format="uuid"),
 *     @OA\Property(property="batch_id",             type="string", format="uuid", nullable=true),
 *     @OA\Property(property="channel",              type="string", enum={"sms","email","push"}),
 *     @OA\Property(property="priority",             type="string", enum={"high","normal","low"}),
 *     @OA\Property(property="status",               type="string", enum={"pending","scheduled","queued","processing","sent","failed","canceled"}),
 *     @OA\Property(property="recipient",            type="string", example="+905551234567"),
 *     @OA\Property(property="content",              type="string", example="Your OTP is 1234"),
 *     @OA\Property(property="metadata",             type="object", nullable=true),
 *     @OA\Property(property="idempotency_key",      type="string", nullable=true),
 *     @OA\Property(property="correlation_id",       type="string", nullable=true),
 *     @OA\Property(property="provider_message_id",  type="string", nullable=true),
 *     @OA\Property(property="error_message",        type="string", nullable=true),
 *     @OA\Property(property="max_attempts",         type="integer", example=3),
 *     @OA\Property(property="attempt_count",        type="integer", example=0),
 *     @OA\Property(property="scheduled_at",         type="string", format="date-time", nullable=true),
 *     @OA\Property(property="queued_at",            type="string", format="date-time", nullable=true),
 *     @OA\Property(property="sent_at",              type="string", format="date-time", nullable=true),
 *     @OA\Property(property="failed_at",            type="string", format="date-time", nullable=true),
 *     @OA\Property(property="canceled_at",          type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at",           type="string", format="date-time"),
 *     @OA\Property(
 *         property="delivery_attempts",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/DeliveryAttemptResource")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="DeliveryAttemptResource",
 *     type="object",
 *
 *     @OA\Property(property="id",                   type="string", format="uuid"),
 *     @OA\Property(property="attempt_number",        type="integer"),
 *     @OA\Property(property="status",                type="string", enum={"success","failed","rate_limited"}),
 *     @OA\Property(property="provider",              type="string"),
 *     @OA\Property(property="provider_message_id",   type="string", nullable=true),
 *     @OA\Property(property="error_message",         type="string", nullable=true),
 *     @OA\Property(property="is_transient_failure",  type="boolean"),
 *     @OA\Property(property="duration_ms",           type="integer"),
 *     @OA\Property(property="attempted_at",          type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="NotificationBatchResource",
 *     type="object",
 *
 *     @OA\Property(property="id",             type="string", format="uuid"),
 *     @OA\Property(property="name",           type="string", nullable=true),
 *     @OA\Property(property="status",         type="string", enum={"pending","processing","completed","failed","partial_failed"}),
 *     @OA\Property(property="metadata",       type="object", nullable=true),
 *     @OA\Property(property="total_count",    type="integer"),
 *     @OA\Property(property="pending_count",  type="integer"),
 *     @OA\Property(property="sent_count",     type="integer"),
 *     @OA\Property(property="failed_count",   type="integer"),
 *     @OA\Property(property="canceled_count", type="integer"),
 *     @OA\Property(property="created_at",     type="string", format="date-time"),
 *     @OA\Property(property="updated_at",     type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="ApiResponse",
 *     type="object",
 *
 *     @OA\Property(property="success", type="boolean"),
 *     @OA\Property(property="data",    type="object")
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="error",   type="object",
 *         @OA\Property(property="code",    type="string"),
 *         @OA\Property(property="message", type="string")
 *     )
 * )
 */
class OpenApi {}
