<?php

namespace Tests\Feature\Api;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
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

    public function test_can_filter_by_status(): void
    {
        Notification::factory()->queued()->create();
        Notification::factory()->sent()->create();
        Notification::factory()->sent()->create();

        $this->getJson('/api/notifications?status=sent')
             ->assertStatus(200)
             ->assertJsonPath('meta.pagination.total', 2);
    }

    public function test_can_filter_by_priority(): void
    {
        Notification::factory()->highPriority()->create();
        Notification::factory()->create(['priority' => NotificationPriority::LOW]);

        $this->getJson('/api/notifications?priority=high')
             ->assertStatus(200)
             ->assertJsonPath('meta.pagination.total', 1);
    }

    public function test_can_filter_by_date_range(): void
    {
        Notification::factory()->create(['created_at' => now()->subDays(10)]);
        Notification::factory()->create(['created_at' => now()->subDays(3)]);
        Notification::factory()->create(['created_at' => now()]);

        $from = now()->subDays(5)->toDateString();
        $to   = now()->toDateString();

        $this->getJson("/api/notifications?from={$from}&to={$to}")
             ->assertStatus(200)
             ->assertJsonPath('meta.pagination.total', 2);
    }

    public function test_list_returns_pagination_meta(): void
    {
        Notification::factory()->count(3)->create();

        $this->getJson('/api/notifications?per_page=2')
             ->assertStatus(200)
             ->assertJsonStructure(['meta' => ['pagination' => [
                 'total', 'per_page', 'current_page', 'last_page',
             ]]]);
    }

    public function test_show_includes_delivery_attempts(): void
    {
        $n = Notification::factory()->create();

        $this->getJson("/api/notifications/{$n->id}")
             ->assertStatus(200)
             ->assertJsonStructure(['data' => ['delivery_attempts']]);
    }
}
