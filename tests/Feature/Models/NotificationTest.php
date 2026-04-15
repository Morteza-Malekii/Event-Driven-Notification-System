<?php

namespace Tests\Feature\Models;

use App\DTOs\DeliveryResponse;
use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_as_queued_updates_status_and_timestamp(): void
    {
        $notification = Notification::factory()->create();

        $notification->markAsQueued();
        $notification->refresh();

        $this->assertEquals(NotificationStatus::QUEUED, $notification->status);
        $this->assertNotNull($notification->queued_at);
    }

    public function test_mark_as_processing_updates_status_and_timestamp(): void
    {
        $notification = Notification::factory()->create();

        $notification->markAsProcessing();
        $notification->refresh();

        $this->assertEquals(NotificationStatus::PROCESSING, $notification->status);
        $this->assertNotNull($notification->processing_at);
    }

    public function test_mark_as_sent(): void
    {
        $n = Notification::factory()->create(['status' => NotificationStatus::PROCESSING]);
        $response = new DeliveryResponse('msg-1', 'accepted', now()->toIso8601String(), 'webhook_site', []);
        $n->markAsSent($response);

        $this->assertEquals(NotificationStatus::SENT, $n->fresh()->status);
        $this->assertNotNull($n->fresh()->sent_at);
        $this->assertEquals('msg-1', $n->fresh()->provider_message_id);
    }

    public function test_mark_as_failed(): void
    {
        $n = Notification::factory()->create(['status' => NotificationStatus::PROCESSING]);
        $n->markAsFailed('timeout');

        $this->assertEquals(NotificationStatus::FAILED, $n->fresh()->status);
        $this->assertEquals('timeout', $n->fresh()->error_message);
    }

    public function test_mark_as_canceled_updates_status_and_timestamp(): void
    {
        $notification = Notification::factory()->create();

        $notification->markAsCanceled();
        $notification->refresh();

        $this->assertEquals(NotificationStatus::CANCELED, $notification->status);
        $this->assertNotNull($notification->canceled_at);
    }

    public function test_is_cancelable(): void
    {
        $this->assertTrue(Notification::factory()->create(['status' => NotificationStatus::QUEUED])->isCancelable());
        $this->assertFalse(Notification::factory()->sent()->create()->isCancelable());
    }

    public function test_has_exceeded_max_attempts_returns_true(): void
    {
        $notification = Notification::factory()->create([
            'max_attempts' => 3,
            'attempt_count' => 3,
        ]);

        $this->assertTrue($notification->hasExceededMaxAttempts());
    }

    public function test_has_exceeded_max_attempts_returns_false(): void
    {
        $notification = Notification::factory()->create([
            'max_attempts' => 3,
            'attempt_count' => 1,
        ]);

        $this->assertFalse($notification->hasExceededMaxAttempts());
    }

    public function test_scope_of_status(): void
    {
        Notification::factory()->create(['status' => NotificationStatus::PENDING]);
        Notification::factory()->sent()->create();

        $results = Notification::ofStatus(NotificationStatus::PENDING)->get();

        $this->assertCount(1, $results);
        $this->assertEquals(NotificationStatus::PENDING, $results->first()->status);
    }

    public function test_scope_of_channel(): void
    {
        Notification::factory()->create(['channel' => NotificationChannel::SMS]);
        Notification::factory()->create(['channel' => NotificationChannel::EMAIL]);
        $this->assertCount(1, Notification::ofChannel('sms')->get());
    }

    public function test_scope_of_priority(): void
    {
        Notification::factory()->create(['priority' => NotificationPriority::NORMAL]);
        Notification::factory()->highPriority()->create();

        $results = Notification::ofPriority(NotificationPriority::HIGH)->get();

        $this->assertCount(1, $results);
    }

    public function test_scope_created_between(): void
    {
        Notification::factory()->create(['created_at' => now()->subDays(2)]);
        Notification::factory()->create(['created_at' => now()]);
        $this->assertCount(1, Notification::createdBetween(
            now()->subDay()->toDateString(), now()->toDateString()
        )->get());
    }
}
