<?php

namespace App\Delivery\Providers;

use App\Delivery\Contracts\ProviderInterface;
use App\DTOs\DeliveryRequest;
use App\DTOs\DeliveryResponse;
use App\Exceptions\ProviderDeliveryException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookSiteProvider implements ProviderInterface
{
    public function name(): string
    {
        return 'webhook_site';
    }

    public function send(DeliveryRequest $request): DeliveryResponse
    {
        $url     = config('notification.webhook.url');
        $timeout = (int) config('notification.webhook.timeout', 15);

        Log::info('Sending notification via webhook', [
            'provider'        => $this->name(),
            'notification_id' => $request->notificationId,
            'channel'         => $request->channel->value,
            'recipient'       => $request->recipient,
        ]);

        try {
            $response = Http::timeout($timeout)
                ->post($url, [
                    'notification_id' => $request->notificationId,
                    'channel'         => $request->channel->value,
                    'recipient'       => $request->recipient,
                    'content'         => $request->content,
                    'metadata'        => $request->metadata,
                    'priority'        => $request->priority->value,
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Webhook provider connection failed', [
                'provider'        => $this->name(),
                'notification_id' => $request->notificationId,
                'error'           => $e->getMessage(),
            ]);

            throw new ProviderDeliveryException(
                'Connection timeout: ' . $e->getMessage(),
                isPermanent: false,
                previous: $e,
            );
        }

        if ($response->clientError()) {
            Log::error('Webhook provider returned 4xx error', [
                'provider'        => $this->name(),
                'notification_id' => $request->notificationId,
                'status'          => $response->status(),
                'body'            => $response->body(),
            ]);

            throw new ProviderDeliveryException(
                "Client error [{$response->status()}]: {$response->body()}",
                isPermanent: true,
            );
        }

        if ($response->serverError()) {
            Log::warning('Webhook provider returned 5xx error', [
                'provider'        => $this->name(),
                'notification_id' => $request->notificationId,
                'status'          => $response->status(),
                'body'            => $response->body(),
            ]);

            throw new ProviderDeliveryException(
                "Server error [{$response->status()}]: {$response->body()}",
                isPermanent: false,
            );
        }

        $body = $response->json() ?? [];

        Log::info('Notification sent successfully via webhook', [
            'provider'        => $this->name(),
            'notification_id' => $request->notificationId,
            'status'          => $response->status(),
        ]);

        return new DeliveryResponse(
            messageId:   $body['message_id'] ?? $request->notificationId,
            status:      'accepted',
            timestamp:   now()->toIso8601String(),
            provider:    $this->name(),
            rawResponse: $body,
        );
    }
}
