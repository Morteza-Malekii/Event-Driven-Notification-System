<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    use HasUuids;

    protected $fillable = [
        'key',
        'request_hash',
        'notification_id',
        'batch_id',
        'response_cache',
        'expires_at',
    ];

    protected $casts = [
        'response_cache' => 'array',
        'expires_at'     => 'datetime',
    ];
}
