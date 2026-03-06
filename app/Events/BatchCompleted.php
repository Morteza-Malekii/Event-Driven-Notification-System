<?php

namespace App\Events;

use App\Models\NotificationBatch;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BatchCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly NotificationBatch $batch,
    ) {}
}
