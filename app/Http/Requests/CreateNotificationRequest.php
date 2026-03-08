<?php

namespace App\Http\Requests;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateNotificationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'channel'         => ['required', Rule::enum(NotificationChannel::class)],
            'recipient'       => ['required', 'string', 'max:255'],
            'content'         => ['required', 'string'],
            'priority'        => ['sometimes', Rule::enum(NotificationPriority::class)],
            'metadata'        => ['sometimes', 'array'],
            'scheduled_at'    => ['sometimes', 'nullable', 'date'],
            'idempotency_key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'max_attempts'    => ['sometimes', 'integer', 'min:1', 'max:10'],
        ];
    }
}
