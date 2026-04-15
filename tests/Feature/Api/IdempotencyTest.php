<?php

namespace Tests\Feature\Api;

use App\Events\NotificationCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class IdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Redis::del(['idempotency:idem-001', 'idempotency:idem-002', 'idempotency:conflict-key']);
    }

    public function test_same_key_same_payload_returns_same_notification(): void
    {
        Event::fake();
        $data = ['channel' => 'sms', 'recipient' => '+98', 'content' => 'test',
            'priority' => 'normal', 'idempotency_key' => 'idem-001'];

        $r1 = $this->postJson('/api/notifications', $data);
        $r2 = $this->postJson('/api/notifications', $data);

        $r1->assertStatus(201);
        $r2->assertStatus(200);
        $this->assertEquals($r1->json('data.id'), $r2->json('data.id'));
    }

    public function test_event_fired_only_once_for_idempotent_requests(): void
    {
        Event::fake([NotificationCreated::class]);
        $data = ['channel' => 'sms', 'recipient' => '+98', 'content' => 'test',
            'priority' => 'normal', 'idempotency_key' => 'idem-002'];

        $this->postJson('/api/notifications', $data);
        $this->postJson('/api/notifications', $data);

        Event::assertDispatchedTimes(NotificationCreated::class, 1);
    }

    public function test_same_key_different_payload_returns_409(): void
    {
        Event::fake();

        $this->postJson('/api/notifications', [
            'channel' => 'sms', 'recipient' => '+98', 'content' => 'original',
            'priority' => 'normal', 'idempotency_key' => 'conflict-key',
        ])->assertStatus(201);

        $this->postJson('/api/notifications', [
            'channel' => 'sms', 'recipient' => '+98', 'content' => 'different content',
            'priority' => 'normal', 'idempotency_key' => 'conflict-key',
        ])->assertStatus(409)
            ->assertJsonPath('error.code', 'DUPLICATE_IDEMPOTENCY_KEY');
    }
}
