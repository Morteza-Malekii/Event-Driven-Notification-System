<?php

namespace Tests\Feature\Listeners;

use App\DTOs\DeliveryResponse;
use App\Enums\BatchStatus;
use App\Events\BatchCompleted;
use App\Events\NotificationSent;
use App\Models\Notification;
use App\Models\NotificationBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SyncBatchCountersListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_increments_sent_count_on_notification_sent(): void
    {
        $batch = NotificationBatch::create([
            'status' => BatchStatus::PROCESSING,
            'total_count' => 2, 'pending_count' => 2,
            'sent_count' => 0, 'failed_count' => 0, 'canceled_count' => 0,
        ]);
        $notification = Notification::factory()->create(['batch_id' => $batch->id]);
        $response = new DeliveryResponse('msg-1', 'accepted', now()->toIso8601String(), 'webhook_site', []);

        event(new NotificationSent($notification, $response, 300));

        $this->assertEquals(1, $batch->fresh()->sent_count);
        $this->assertEquals(1, $batch->fresh()->pending_count);
    }

    public function test_fires_batch_completed_when_all_sent(): void
    {
        Event::fake([BatchCompleted::class]);

        $batch = NotificationBatch::create([
            'status' => BatchStatus::PROCESSING,
            'total_count' => 1, 'pending_count' => 1,
            'sent_count' => 0, 'failed_count' => 0, 'canceled_count' => 0,
        ]);
        $notification = Notification::factory()->create(['batch_id' => $batch->id]);
        $response = new DeliveryResponse('msg-1', 'accepted', now()->toIso8601String(), 'webhook_site', []);

        event(new NotificationSent($notification, $response, 300));

        Event::assertDispatched(BatchCompleted::class);
    }

    public function test_skips_notifications_without_batch(): void
    {
        Event::fake([BatchCompleted::class]);
        $notification = Notification::factory()->create(['batch_id' => null]);
        $response = new DeliveryResponse('msg-1', 'accepted', now()->toIso8601String(), 'webhook_site', []);

        event(new NotificationSent($notification, $response, 200));

        Event::assertNotDispatched(BatchCompleted::class);
    }
}
