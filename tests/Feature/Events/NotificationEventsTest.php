<?php

namespace Tests\Feature\Events;

use App\DTOs\DeliveryResponse;
use App\Events\BatchCompleted;
use App\Events\NotificationCanceled;
use App\Events\NotificationCreated;
use App\Events\NotificationFailed;
use App\Events\NotificationSent;
use App\Models\Notification;
use App\Models\NotificationBatch;
use Tests\TestCase;

class NotificationEventsTest extends TestCase
{
    public function test_notification_created_event_holds_notification(): void
    {
        $notification = Notification::factory()->make();
        $event = new NotificationCreated($notification);
        $this->assertSame($notification, $event->notification);
    }

    public function test_notification_sent_event_holds_response(): void
    {
        $notification = Notification::factory()->make();
        $response = new DeliveryResponse('msg-1', 'accepted', now()->toIso8601String(), 'webhook_site', []);
        $event = new NotificationSent($notification, $response, 250);

        $this->assertSame($notification, $event->notification);
        $this->assertSame($response, $event->response);
        $this->assertEquals(250, $event->durationMs);
    }

    public function test_notification_failed_event_holds_error_info(): void
    {
        $notification = Notification::factory()->make();
        $event = new NotificationFailed($notification, 'timeout', false, 500);

        $this->assertEquals('timeout', $event->errorMessage);
        $this->assertFalse($event->isPermanent);
        $this->assertEquals(500, $event->durationMs);
    }

    public function test_notification_canceled_event_holds_notification(): void
    {
        $notification = Notification::factory()->make();
        $event = new NotificationCanceled($notification);
        $this->assertSame($notification, $event->notification);
    }

    public function test_batch_completed_event_holds_batch(): void
    {
        $batch = NotificationBatch::factory()->make();
        $event = new BatchCompleted($batch);
        $this->assertSame($batch, $event->batch);
    }
}
