<?php

namespace Tests\Feature\Listeners;

use App\DTOs\DeliveryResponse;
use App\Enums\NotificationChannel;
use App\Events\NotificationFailed;
use App\Events\NotificationSent;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class RecordMetricsListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_increments_sent_counter_on_notification_sent(): void
    {
        Redis::del('metrics:sent:sms');
        $notification = Notification::factory()->create(['channel' => NotificationChannel::SMS]);
        $response = new DeliveryResponse('msg-1', 'accepted', now()->toIso8601String(), 'webhook_site', []);

        event(new NotificationSent($notification, $response, 300));

        $this->assertEquals(1, (int) Redis::get('metrics:sent:sms'));
    }

    public function test_increments_failed_counter_on_notification_failed(): void
    {
        Redis::del('metrics:failed:email');
        $notification = Notification::factory()->create(['channel' => NotificationChannel::EMAIL]);

        event(new NotificationFailed($notification, 'timeout', false, 500));

        $this->assertEquals(1, (int) Redis::get('metrics:failed:email'));
    }

    public function test_records_latency_on_sent(): void
    {
        Redis::del('metrics:latency:sms');
        $notification = Notification::factory()->create(['channel' => NotificationChannel::SMS]);
        $response = new DeliveryResponse('msg-1', 'accepted', now()->toIso8601String(), 'webhook_site', []);

        event(new NotificationSent($notification, $response, 250));

        $samples = Redis::lrange('metrics:latency:sms', 0, -1);
        $this->assertContains('250', $samples);
    }
}
