<?php

namespace App\Models;

use App\Enums\BatchStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class NotificationBatch extends Model
{
    use HasFactory, HasUuids;

    protected $attributes = [
        'status'         => 'pending',
        'total_count'    => 0,
        'pending_count'  => 0,
        'sent_count'     => 0,
        'failed_count'   => 0,
        'canceled_count' => 0,
    ];

    protected $fillable = [
        'name',
        'status',
        'metadata',
        'total_count',
        'pending_count',
        'sent_count',
        'failed_count',
        'canceled_count',
    ];

    protected $casts = [
        'status'   => BatchStatus::class,
        'metadata' => 'array',
    ];

    // Relations

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'batch_id');
    }

    // Counter helpers

    public function incrementCounter(string $column): void
    {
        DB::table('notification_batches')
            ->where('id', $this->id)
            ->increment($column);
    }

    public function decrementCounter(string $column): void
    {
        DB::table('notification_batches')
            ->where('id', $this->id)
            ->decrement($column);
    }

    // Status recalculation

    public function recalculateStatus(): void
    {
        $this->refresh();

        $status = match (true) {
            $this->total_count === 0
                => BatchStatus::PENDING,

            $this->pending_count === 0 && $this->failed_count === 0 && $this->canceled_count === 0
                => BatchStatus::COMPLETED,

            $this->pending_count === 0 && $this->sent_count > 0 && $this->failed_count > 0
                => BatchStatus::PARTIAL_FAILED,

            $this->pending_count === 0 && $this->sent_count === 0
                => BatchStatus::FAILED,

            $this->pending_count > 0
                => BatchStatus::PROCESSING,

            default => BatchStatus::PROCESSING,
        };

        $this->updateQuietly(['status' => $status]);
    }
}
