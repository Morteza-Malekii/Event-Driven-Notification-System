<?php

namespace Database\Factories;

use App\Enums\BatchStatus;
use App\Models\NotificationBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationBatchFactory extends Factory
{
    protected $model = NotificationBatch::class;

    public function definition(): array
    {
        return [
            'status'         => BatchStatus::PENDING,
            'total_count'    => 0,
            'pending_count'  => 0,
            'sent_count'     => 0,
            'failed_count'   => 0,
            'canceled_count' => 0,
        ];
    }
}
