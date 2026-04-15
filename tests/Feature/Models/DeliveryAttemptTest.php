<?php

namespace Tests\Feature\Models;

use App\Enums\DeliveryAttemptStatus;
use App\Models\DeliveryAttempt;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DeliveryAttemptTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_no_updated_at_column(): void
    {
        $this->assertFalse(Schema::hasColumn('delivery_attempts', 'updated_at'));
    }

    public function test_updated_at_constant_is_null(): void
    {
        $this->assertNull(DeliveryAttempt::UPDATED_AT);
    }

    public function test_belongs_to_notification(): void
    {
        $notification = Notification::factory()->create();

        $attempt = DeliveryAttempt::create([
            'notification_id' => $notification->id,
            'attempt_number' => 1,
            'status' => DeliveryAttemptStatus::SUCCESS,
            'attempted_at' => now(),
        ]);

        $this->assertTrue($attempt->notification->is($notification));
    }

    public function test_notification_has_many_delivery_attempts(): void
    {
        $notification = Notification::factory()->create();

        DeliveryAttempt::create([
            'notification_id' => $notification->id,
            'attempt_number' => 1,
            'status' => DeliveryAttemptStatus::FAILED,
            'attempted_at' => now(),
        ]);

        DeliveryAttempt::create([
            'notification_id' => $notification->id,
            'attempt_number' => 2,
            'status' => DeliveryAttemptStatus::SUCCESS,
            'attempted_at' => now(),
        ]);

        $this->assertCount(2, $notification->deliveryAttempts);
    }
}
