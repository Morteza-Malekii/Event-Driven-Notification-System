<?php

namespace Tests\Feature\Api;

use App\Enums\NotificationChannel;
use App\Events\NotificationCreated;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_notification(): void
    {
        Event::fake();
        $this->postJson('/api/notifications', [
            'channel' => 'sms', 'recipient' => '+989123456789',
            'content' => 'test', 'priority' => 'high',
        ])->assertStatus(201)
          ->assertJsonPath('success', true)
          ->assertJsonPath('data.channel', 'sms');
    }

    public function test_fires_event_on_create(): void
    {
        Event::fake([NotificationCreated::class]);
        $this->postJson('/api/notifications', [
            'channel' => 'sms', 'recipient' => '+98',
            'content' => 'test', 'priority' => 'high',
        ]);
        Event::assertDispatched(NotificationCreated::class);
    }

    public function test_validation_fails_for_missing_fields(): void
    {
        $this->postJson('/api/notifications', [])
             ->assertStatus(422)
             ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_can_get_notification(): void
    {
        $n = Notification::factory()->create();
        $this->getJson("/api/notifications/{$n->id}")
             ->assertStatus(200)
             ->assertJsonPath('data.id', $n->id);
    }

    public function test_returns_404_for_unknown_id(): void
    {
        $this->getJson('/api/notifications/00000000-0000-0000-0000-000000000000')
             ->assertStatus(404);
    }

    public function test_can_filter_by_channel(): void
    {
        Notification::factory()->create(['channel' => NotificationChannel::SMS]);
        Notification::factory()->create(['channel' => NotificationChannel::EMAIL]);

        $this->getJson('/api/notifications?channel=sms')
             ->assertStatus(200)
             ->assertJsonPath('meta.pagination.total', 1);
    }
}
