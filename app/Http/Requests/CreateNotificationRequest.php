<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateNotificationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'channel'         => ['required', 'string', 'in:sms,email,push'],
            'recipient'       => ['required', 'string', 'max:255'],
            'content'         => ['required', 'string'],
            'priority'        => ['sometimes', 'string', 'in:high,normal,low'],
            'metadata'        => ['sometimes', 'array'],
            'scheduled_at'    => ['sometimes', 'nullable', 'date'],
            'idempotency_key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'max_attempts'    => ['sometimes', 'integer', 'min:1', 'max:10'],
        ];
    }
}
