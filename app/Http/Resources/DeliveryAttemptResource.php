<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'attempt_number'       => $this->attempt_number,
            'status'               => $this->status->value,
            'provider'             => $this->provider,
            'provider_message_id'  => $this->provider_message_id,
            'error_message'        => $this->error_message,
            'is_transient_failure' => $this->is_transient_failure,
            'duration_ms'          => $this->duration_ms,
            'attempted_at'         => $this->attempted_at?->toIso8601String(),
        ];
    }
}
