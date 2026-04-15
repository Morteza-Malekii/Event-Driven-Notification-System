<?php

namespace Tests\Feature\Actions;

use App\Actions\CreateNotificationAction;
use App\Events\NotificationCreated;
use App\Exceptions\DuplicateIdempotencyKeyException;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class CreateNotificationActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Redis::del(['idempotency:idem-001', 'idempotency:idem-002', 'idempotency:idem-003']);
    }

    public function test_creates_notification(): void
    {
        Event::fake();
        $n = app(CreateNotificationAction::class)->execute([
            'channel' => 'sms', 'recipient' => '+98',
            'content' => 'test', 'priority' => 'high',
            'correlation_id' => 'corr-1',
        ]);

        $this->assertInstanceOf(Notification::class, $n);
        $this->assertDatabaseHas('notifications', ['id' => $n->id]);
    }

    public function test_fires_notification_created_event(): void
    {
        Event::fake([NotificationCreated::class]);
        $n = app(CreateNotificationAction::class)->execute([
            'channel' => 'sms', 'recipient' => '+98',
            'content' => 'test', 'priority' => 'normal',
            'correlation_id' => 'corr-1',
        ]);

        Event::assertDispatched(NotificationCreated::class,
            fn ($e) => $e->notification->id === $n->id
        );
    }

    public function test_does_not_fire_event_on_idempotency_replay(): void
    {
        Event::fake([NotificationCreated::class]);
        $data = ['channel' => 'sms', 'recipient' => '+98', 'content' => 'test',
            'priority' => 'normal', 'idempotency_key' => 'idem-001', 'correlation_id' => 'c1'];

        app(CreateNotificationAction::class)->execute($data);
        app(CreateNotificationAction::class)->execute($data); // replay

        Event::assertDispatchedTimes(NotificationCreated::class, 1);
    }

    public function test_idempotency_returns_same_notification(): void
    {
        Event::fake();
        $data = ['channel' => 'sms', 'recipient' => '+98', 'content' => 'test',
            'priority' => 'normal', 'idempotency_key' => 'idem-002', 'correlation_id' => 'c1'];

        $n1 = app(CreateNotificationAction::class)->execute($data);
        $n2 = app(CreateNotificationAction::class)->execute($data);

        $this->assertEquals($n1->id, $n2->id);
    }

    public function test_throws_on_idempotency_conflict(): void
    {
        Event::fake();
        app(CreateNotificationAction::class)->execute([
            'channel' => 'sms', 'recipient' => '+98', 'content' => 'original',
            'priority' => 'normal', 'idempotency_key' => 'idem-003', 'correlation_id' => 'c1',
        ]);

        $this->expectException(DuplicateIdempotencyKeyException::class);
        app(CreateNotificationAction::class)->execute([
            'channel' => 'sms', 'recipient' => '+98', 'content' => 'different',
            'priority' => 'normal', 'idempotency_key' => 'idem-003', 'correlation_id' => 'c1',
        ]);
    }
}
