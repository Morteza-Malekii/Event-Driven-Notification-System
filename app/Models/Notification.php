<?php

namespace App\Models;

use App\DTOs\DeliveryResponse;
use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notification extends Model
{
    use HasFactory, HasUuids;

    protected $attributes = [
        'status' => 'pending',
        'priority' => 'normal',
        'max_attempts' => 3,
        'attempt_count' => 0,
    ];

    protected $fillable = [
        'batch_id',
        'channel',
        'priority',
        'status',
        'recipient',
        'content',
        'metadata',
        'idempotency_key',
        'max_attempts',
        'attempt_count',
        'correlation_id',
        'provider_message_id',
        'error_message',
        'scheduled_at',
        'queued_at',
        'processing_at',
        'sent_at',
        'delivered_at',
        'failed_at',
        'canceled_at',
        'last_attempted_at',
    ];

    protected $casts = [
        'status' => NotificationStatus::class,
        'channel' => NotificationChannel::class,
        'priority' => NotificationPriority::class,
        'metadata' => 'array',
        'scheduled_at' => 'datetime',
        'queued_at' => 'datetime',
        'processing_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'canceled_at' => 'datetime',
        'last_attempted_at' => 'datetime',
    ];

    // Relations

    public function batch(): BelongsTo
    {
        return $this->belongsTo(NotificationBatch::class, 'batch_id');
    }

    public function deliveryAttempts(): HasMany
    {
        return $this->hasMany(DeliveryAttempt::class);
    }

    // Scopes

    public function scopeOfStatus($query, NotificationStatus|string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOfChannel($query, NotificationChannel|string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeOfPriority($query, NotificationPriority|string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeCreatedBetween($query, string $from, string $to)
    {
        return $query->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);
    }

    // Status helpers

    public function markAsScheduled(): bool
    {
        return $this->updateQuietly([
            'status' => NotificationStatus::SCHEDULED,
        ]);
    }

    public function markAsQueued(): bool
    {
        return $this->updateQuietly([
            'status' => NotificationStatus::QUEUED,
            'queued_at' => now(),
        ]);
    }

    public function markAsProcessing(): bool
    {
        return $this->updateQuietly([
            'status' => NotificationStatus::PROCESSING,
            'processing_at' => now(),
        ]);
    }

    public function markAsSent(DeliveryResponse $response): bool
    {
        return $this->updateQuietly([
            'status' => NotificationStatus::SENT,
            'sent_at' => now(),
            'provider_message_id' => $response->messageId,
            'last_attempted_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): bool
    {
        return $this->updateQuietly([
            'status' => NotificationStatus::FAILED,
            'failed_at' => now(),
            'error_message' => $errorMessage,
            'last_attempted_at' => now(),
        ]);
    }

    public function markAsCanceled(): bool
    {
        return $this->updateQuietly([
            'status' => NotificationStatus::CANCELED,
            'canceled_at' => now(),
        ]);
    }

    // Helpers

    public function isCancelable(): bool
    {
        return $this->status->isCancelable();
    }

    public function hasExceededMaxAttempts(): bool
    {
        return $this->attempt_count >= $this->max_attempts;
    }
}
