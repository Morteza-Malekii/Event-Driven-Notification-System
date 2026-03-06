<?php

namespace Tests\Feature\Listeners;

use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use App\Events\NotificationCreated;
use App\Jobs\ProcessNotificationJob;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DispatchNotificationJobListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_job_on_notification_created(): void
    {
        Queue::fake();
        $notification = Notification::factory()->create([
            'status'   => NotificationStatus::PENDING,
            'priority' => NotificationPriority::HIGH,
        ]);

        event(new NotificationCreated($notification));

        Queue::assertPushedOn('notifications-high', ProcessNotificationJob::class);
    }

    public function test_does_not_dispatch_for_scheduled_notification(): void
    {
        Queue::fake();
        $notification = Notification::factory()->create([
            'status'       => NotificationStatus::SCHEDULED,
            'scheduled_at' => now()->addHour(),
        ]);

        event(new NotificationCreated($notification));

        Queue::assertNothingPushed();
    }

    public function test_marks_notification_as_queued(): void
    {
        Queue::fake();
        $notification = Notification::factory()->create(['status' => NotificationStatus::PENDING]);

        event(new NotificationCreated($notification));

        $this->assertEquals(NotificationStatus::QUEUED, $notification->fresh()->status);
        $this->assertNotNull($notification->fresh()->queued_at);
    }
}
