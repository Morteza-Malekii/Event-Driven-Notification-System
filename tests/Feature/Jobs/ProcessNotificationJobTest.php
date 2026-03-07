<?php

namespace Tests\Feature\Jobs;

use App\Delivery\ProviderFactory;
use App\Enums\NotificationStatus;
use App\Events\NotificationFailed;
use App\Events\NotificationSent;
use App\Exceptions\ProviderDeliveryException;
use App\Jobs\ProcessNotificationJob;
use App\Models\DeliveryAttempt;
use App\Models\Notification;
use App\Services\RateLimiterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use RuntimeException;
use Tests\TestCase;

class ProcessNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['notification.providers.webhook_site.url' => 'https://webhook.test/uuid']);
        Redis::del(['rate_limit:channel:sms', 'rate_limit:channel:email', 'rate_limit:channel:push']);
    }

    private function runJob(string $notificationId): void
    {
        (new ProcessNotificationJob($notificationId))->handle(
            app(ProviderFactory::class),
            app(RateLimiterService::class),
        );
    }

    public function test_returns_early_when_notification_not_found(): void
    {
        Event::fake([NotificationSent::class, NotificationFailed::class]);
        Http::fake();

        $this->runJob('00000000-0000-0000-0000-000000000000');

        Event::assertNotDispatched(NotificationSent::class);
        Event::assertNotDispatched(NotificationFailed::class);
        Http::assertNothingSent();
    }

    public function test_returns_early_when_notification_is_terminal(): void
    {
        Event::fake([NotificationSent::class, NotificationFailed::class]);
        Http::fake();

        $notification = Notification::factory()->sent()->create();

        $this->runJob($notification->id);

        Event::assertNotDispatched(NotificationSent::class);
        Event::assertNotDispatched(NotificationFailed::class);
        Http::assertNothingSent();
    }

    public function test_marks_notification_as_sent_on_success(): void
    {
        Event::fake([NotificationSent::class]);
        Http::fake(['*' => Http::response(['message_id' => 'msg-abc'], 200)]);

        $notification = Notification::factory()->queued()->create();

        $this->runJob($notification->id);

        $this->assertEquals(NotificationStatus::SENT, $notification->fresh()->status);
        $this->assertNotNull($notification->fresh()->sent_at);
        $this->assertEquals('msg-abc', $notification->fresh()->provider_message_id);
    }

    public function test_fires_notification_sent_event_on_success(): void
    {
        Event::fake([NotificationSent::class]);
        Http::fake(['*' => Http::response(['message_id' => 'msg-xyz'], 200)]);

        $notification = Notification::factory()->queued()->create();

        $this->runJob($notification->id);

        Event::assertDispatched(NotificationSent::class,
            fn($e) => $e->notification->id === $notification->id
        );
    }

    public function test_marks_notification_as_failed_on_permanent_4xx(): void
    {
        Event::fake([NotificationFailed::class]);
        Http::fake(['*' => Http::response([], 422)]);

        $notification = Notification::factory()->queued()->create();

        $this->runJob($notification->id);

        $this->assertEquals(NotificationStatus::FAILED, $notification->fresh()->status);
        $this->assertNotNull($notification->fresh()->failed_at);
    }

    public function test_fires_notification_failed_with_permanent_flag_on_4xx(): void
    {
        Event::fake([NotificationFailed::class]);
        Http::fake(['*' => Http::response([], 422)]);

        $notification = Notification::factory()->queued()->create();

        $this->runJob($notification->id);

        Event::assertDispatched(NotificationFailed::class,
            fn($e) => $e->isPermanent === true && $e->notification->id === $notification->id
        );
    }

    public function test_creates_delivery_attempt_on_permanent_failure(): void
    {
        Event::fake();
        Http::fake(['*' => Http::response([], 422)]);

        $notification = Notification::factory()->queued()->create();

        $this->runJob($notification->id);

        $this->assertDatabaseHas('delivery_attempts', [
            'notification_id'      => $notification->id,
            'is_transient_failure' => 0,
        ]);
    }

    public function test_fires_notification_failed_with_transient_flag_on_5xx(): void
    {
        Event::fake([NotificationFailed::class]);
        Http::fake(['*' => Http::response([], 503)]);

        $notification = Notification::factory()->queued()->create();

        try {
            $this->runJob($notification->id);
        } catch (ProviderDeliveryException) {}

        Event::assertDispatched(NotificationFailed::class,
            fn($e) => $e->isPermanent === false && $e->notification->id === $notification->id
        );
    }

    public function test_creates_delivery_attempt_on_transient_failure(): void
    {
        Event::fake();
        Http::fake(['*' => Http::response([], 503)]);

        $notification = Notification::factory()->queued()->create();

        try {
            $this->runJob($notification->id);
        } catch (ProviderDeliveryException) {}

        $this->assertDatabaseHas('delivery_attempts', [
            'notification_id'      => $notification->id,
            'is_transient_failure' => 1,
        ]);
    }

    public function test_rethrows_exception_on_transient_failure(): void
    {
        Event::fake();
        Http::fake(['*' => Http::response([], 503)]);

        $notification = Notification::factory()->queued()->create();

        $this->expectException(ProviderDeliveryException::class);
        $this->runJob($notification->id);
    }

    public function test_stays_processing_when_rate_limited(): void
    {
        Event::fake();
        Http::fake();

        $notification = Notification::factory()->queued()->create();

        $rateLimiter = $this->createMock(RateLimiterService::class);
        $rateLimiter->method('attempt')->willReturn(false);

        (new ProcessNotificationJob($notification->id))->handle(
            app(ProviderFactory::class),
            $rateLimiter,
        );

        $this->assertEquals(NotificationStatus::PROCESSING, $notification->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_failed_hook_marks_notification_as_failed(): void
    {
        $notification = Notification::factory()->queued()->create();

        $job = new ProcessNotificationJob($notification->id);
        $job->failed(new RuntimeException('Unexpected queue failure'));

        $this->assertEquals(NotificationStatus::FAILED, $notification->fresh()->status);
        $this->assertNotNull($notification->fresh()->failed_at);
    }
}
