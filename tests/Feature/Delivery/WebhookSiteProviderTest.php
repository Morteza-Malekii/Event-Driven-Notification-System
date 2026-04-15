<?php

namespace Tests\Feature\Delivery;

use App\Delivery\Providers\WebhookSiteProvider;
use App\DTOs\DeliveryRequest;
use App\DTOs\DeliveryResponse;
use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Exceptions\ProviderDeliveryException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebhookSiteProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['notification.providers.webhook_site.url' => 'https://webhook.test/uuid']);
    }

    public function test_name_returns_webhook_site(): void
    {
        $this->assertEquals('webhook_site', app(WebhookSiteProvider::class)->name());
    }

    public function test_returns_delivery_response_on_success(): void
    {
        Http::fake(['*' => Http::response(['id' => 'msg-123'], 200)]);
        $response = app(WebhookSiteProvider::class)->send(
            new DeliveryRequest('uuid-1', NotificationChannel::SMS, '+98', 'test', [], NotificationPriority::HIGH)
        );
        $this->assertInstanceOf(DeliveryResponse::class, $response);
    }

    public function test_throws_permanent_exception_on_4xx(): void
    {
        Http::fake(['*' => Http::response([], 422)]);
        $this->expectException(ProviderDeliveryException::class);
        app(WebhookSiteProvider::class)->send(
            new DeliveryRequest('uuid-1', NotificationChannel::SMS, 'bad', 'test', [], NotificationPriority::NORMAL)
        );
    }

    public function test_4xx_exception_is_permanent(): void
    {
        Http::fake(['*' => Http::response([], 422)]);
        try {
            app(WebhookSiteProvider::class)->send(
                new DeliveryRequest('uuid-1', NotificationChannel::SMS, 'bad', 'test', [], NotificationPriority::NORMAL)
            );
        } catch (ProviderDeliveryException $e) {
            $this->assertTrue($e->isPermanent);
        }
    }

    public function test_throws_transient_exception_on_5xx(): void
    {
        Http::fake(['*' => Http::response([], 503)]);
        try {
            app(WebhookSiteProvider::class)->send(
                new DeliveryRequest('uuid-1', NotificationChannel::SMS, '+98', 'test', [], NotificationPriority::NORMAL)
            );
        } catch (ProviderDeliveryException $e) {
            $this->assertFalse($e->isPermanent);
        }
    }
}
