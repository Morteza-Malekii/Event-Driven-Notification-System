<?php

namespace Tests\Feature\Api;

use App\Enums\NotificationStatus;
use App\Events\NotificationCanceled;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_cancel_queued_notification(): void
    {
        Event::fake();
        $n = Notification::factory()->create(['status' => NotificationStatus::QUEUED]);

        $this->postJson("/api/notifications/{$n->id}/cancel")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'canceled');
    }

    public function test_fires_canceled_event_on_cancel(): void
    {
        Event::fake([NotificationCanceled::class]);
        $n = Notification::factory()->create(['status' => NotificationStatus::QUEUED]);

        $this->postJson("/api/notifications/{$n->id}/cancel");

        Event::assertDispatched(NotificationCanceled::class);
    }

    public function test_cannot_cancel_sent_notification(): void
    {
        Event::fake();
        $n = Notification::factory()->sent()->create();

        $this->postJson("/api/notifications/{$n->id}/cancel")
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'CANCELLATION_NOT_ALLOWED');
    }
}
