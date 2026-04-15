<?php

namespace Database\Factories;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'channel' => NotificationChannel::EMAIL,
            'priority' => NotificationPriority::NORMAL,
            'status' => NotificationStatus::PENDING,
            'recipient' => fake()->email(),
            'content' => fake()->sentence(),
            'metadata' => null,
            'max_attempts' => 3,
            'attempt_count' => 0,
        ];
    }

    public function sms(): static
    {
        return $this->state([
            'channel' => NotificationChannel::SMS,
            'recipient' => fake()->phoneNumber(),
        ]);
    }

    public function email(): static
    {
        return $this->state([
            'channel' => NotificationChannel::EMAIL,
            'recipient' => fake()->email(),
        ]);
    }

    public function push(): static
    {
        return $this->state([
            'channel' => NotificationChannel::PUSH,
            'recipient' => fake()->uuid(),
        ]);
    }

    public function highPriority(): static
    {
        return $this->state([
            'priority' => NotificationPriority::HIGH,
        ]);
    }

    public function queued(): static
    {
        return $this->state([
            'status' => NotificationStatus::QUEUED,
            'queued_at' => now(),
        ]);
    }

    public function sent(): static
    {
        return $this->state([
            'status' => NotificationStatus::SENT,
            'sent_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => NotificationStatus::FAILED,
            'failed_at' => now(),
            'error_message' => fake()->sentence(),
        ]);
    }
}
