<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'batch_id'            => $this->batch_id,
            'channel'             => $this->channel->value,
            'priority'            => $this->priority->value,
            'status'              => $this->status->value,
            'recipient'           => $this->recipient,
            'content'             => $this->content,
            'metadata'            => $this->metadata,
            'idempotency_key'     => $this->idempotency_key,
            'correlation_id'      => $this->correlation_id,
            'provider_message_id' => $this->provider_message_id,
            'error_message'       => $this->error_message,
            'max_attempts'        => $this->max_attempts,
            'attempt_count'       => $this->attempt_count,
            'scheduled_at'        => $this->scheduled_at?->toIso8601String(),
            'queued_at'           => $this->queued_at?->toIso8601String(),
            'sent_at'             => $this->sent_at?->toIso8601String(),
            'failed_at'           => $this->failed_at?->toIso8601String(),
            'canceled_at'         => $this->canceled_at?->toIso8601String(),
            'created_at'          => $this->created_at?->toIso8601String(),
            'delivery_attempts'   => DeliveryAttemptResource::collection(
                $this->whenLoaded('deliveryAttempts')
            ),
        ];
    }
}
