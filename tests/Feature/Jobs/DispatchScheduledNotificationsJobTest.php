<?php

namespace Tests\Feature\Jobs;

use App\Events\NotificationCreated;
use App\Enums\NotificationStatus;
use App\Jobs\DispatchScheduledNotificationsJob;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DispatchScheduledNotificationsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_fires_notification_created_for_due_notifications(): void
    {
        Event::fake([NotificationCreated::class]);
        Notification::factory()->create([
            'status'       => NotificationStatus::SCHEDULED,
            'scheduled_at' => now()->subMinute(),
        ]);

        (new DispatchScheduledNotificationsJob())->handle();

        Event::assertDispatched(NotificationCreated::class);
    }

    public function test_ignores_future_notifications(): void
    {
        Event::fake([NotificationCreated::class]);
        Notification::factory()->create([
            'status'       => NotificationStatus::SCHEDULED,
            'scheduled_at' => now()->addHour(),
        ]);

        (new DispatchScheduledNotificationsJob())->handle();

        Event::assertNotDispatched(NotificationCreated::class);
    }

    public function test_does_not_fire_for_non_scheduled_notifications(): void
    {
        Event::fake([NotificationCreated::class]);
        Notification::factory()->create([
            'status'       => NotificationStatus::PENDING,
            'scheduled_at' => now()->subMinute(),
        ]);

        (new DispatchScheduledNotificationsJob())->handle();

        Event::assertNotDispatched(NotificationCreated::class);
    }
}
