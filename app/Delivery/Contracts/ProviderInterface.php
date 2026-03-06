<?php

namespace App\Delivery\Contracts;

use App\DTOs\DeliveryRequest;
use App\DTOs\DeliveryResponse;

interface ProviderInterface
{
    public function send(DeliveryRequest $request): DeliveryResponse;

    public function name(): string;
}
