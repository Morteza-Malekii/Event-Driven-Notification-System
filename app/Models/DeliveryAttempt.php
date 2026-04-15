<?php

namespace App\Models;

use App\Enums\DeliveryAttemptStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryAttempt extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $fillable = [
        'notification_id',
        'attempt_number',
        'status',
        'provider',
        'provider_message_id',
        'error_message',
        'is_transient_failure',
        'duration_ms',
        'attempted_at',
    ];

    protected $casts = [
        'status' => DeliveryAttemptStatus::class,
        'is_transient_failure' => 'boolean',
        'attempted_at' => 'datetime',
    ];

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }
}
