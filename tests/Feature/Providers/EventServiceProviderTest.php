<?php

namespace Tests\Feature\Providers;

use App\Events\NotificationCanceled;
use App\Events\NotificationCreated;
use App\Events\NotificationFailed;
use App\Events\NotificationSent;
use App\Listeners\DispatchNotificationJobListener;
use App\Listeners\RecordMetricsListener;
use App\Listeners\SyncBatchCountersListener;
use App\Providers\EventServiceProvider;
use Tests\TestCase;

class EventServiceProviderTest extends TestCase
{
    private function listenersFor(string $event): array
    {
        $listen = app()->getProvider(EventServiceProvider::class)->listens();
        $listeners = $listen[$event] ?? [];

        return array_map(fn ($l) => is_array($l) ? $l[0] : $l, $listeners);
    }

    public function test_notification_created_has_dispatch_listener(): void
    {
        $this->assertContains(
            DispatchNotificationJobListener::class,
            $this->listenersFor(NotificationCreated::class),
        );
    }

    public function test_notification_sent_has_metrics_and_batch_listeners(): void
    {
        $classNames = $this->listenersFor(NotificationSent::class);

        $this->assertContains(RecordMetricsListener::class, $classNames);
        $this->assertContains(SyncBatchCountersListener::class, $classNames);
    }

    public function test_notification_failed_has_metrics_and_batch_listeners(): void
    {
        $classNames = $this->listenersFor(NotificationFailed::class);

        $this->assertContains(RecordMetricsListener::class, $classNames);
        $this->assertContains(SyncBatchCountersListener::class, $classNames);
    }

    public function test_notification_canceled_has_batch_listener(): void
    {
        $this->assertContains(
            SyncBatchCountersListener::class,
            $this->listenersFor(NotificationCanceled::class),
        );
    }

    public function test_event_discovery_is_disabled(): void
    {
        $provider = app()->getProvider(EventServiceProvider::class);

        $this->assertFalse($provider->shouldDiscoverEvents());
    }
}
