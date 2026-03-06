<?php

namespace App\Delivery;

use App\Delivery\Contracts\ProviderInterface;
use App\Delivery\Providers\WebhookSiteProvider;
use App\Enums\NotificationChannel;

class ProviderFactory
{
    private array $channelMap = [
        NotificationChannel::SMS->value   => WebhookSiteProvider::class,
        NotificationChannel::EMAIL->value => WebhookSiteProvider::class,
        NotificationChannel::PUSH->value  => WebhookSiteProvider::class,
    ];

    public function make(NotificationChannel $channel): ProviderInterface
    {
        $providerClass = $this->channelMap[$channel->value];

        return app($providerClass);
    }
}
