<?php

namespace Tests\Feature\Actions;

use App\Actions\CancelNotificationAction;
use App\Enums\NotificationStatus;
use App\Events\NotificationCanceled;
use App\Exceptions\CancellationNotAllowedException;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CancelNotificationActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancels_queued_notification(): void
    {
        Event::fake();
        $n = Notification::factory()->create(['status' => NotificationStatus::QUEUED]);

        $result = app(CancelNotificationAction::class)->execute($n->id);

        $this->assertEquals(NotificationStatus::CANCELED, $result->fresh()->status);
        $this->assertNotNull($result->fresh()->canceled_at);
    }

    public function test_fires_notification_canceled_event(): void
    {
        Event::fake([NotificationCanceled::class]);
        $n = Notification::factory()->create(['status' => NotificationStatus::QUEUED]);

        app(CancelNotificationAction::class)->execute($n->id);

        Event::assertDispatched(NotificationCanceled::class,
            fn ($e) => $e->notification->id === $n->id
        );
    }

    public function test_throws_when_not_cancelable(): void
    {
        Event::fake();
        $n = Notification::factory()->sent()->create();

        $this->expectException(CancellationNotAllowedException::class);
        app(CancelNotificationAction::class)->execute($n->id);
    }

    public function test_does_not_fire_event_when_not_cancelable(): void
    {
        Event::fake([NotificationCanceled::class]);
        $n = Notification::factory()->sent()->create();

        try {
            app(CancelNotificationAction::class)->execute($n->id);
        } catch (CancellationNotAllowedException) {
        }

        Event::assertNotDispatched(NotificationCanceled::class);
    }
}
