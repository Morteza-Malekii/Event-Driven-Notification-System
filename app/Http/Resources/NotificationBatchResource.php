<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationBatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'status'          => $this->status->value,
            'metadata'        => $this->metadata,
            'total_count'     => $this->total_count,
            'pending_count'   => $this->pending_count,
            'sent_count'      => $this->sent_count,
            'failed_count'    => $this->failed_count,
            'canceled_count'  => $this->canceled_count,
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }
}
