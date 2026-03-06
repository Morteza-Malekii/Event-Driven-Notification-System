<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateBatchNotificationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'                        => ['sometimes', 'nullable', 'string', 'max:255'],
            'metadata'                    => ['sometimes', 'array'],
            'notifications'               => ['required', 'array', 'min:1', 'max:1000'],
            'notifications.*.channel'     => ['required', 'string', 'in:sms,email,push'],
            'notifications.*.recipient'   => ['required', 'string', 'max:255'],
            'notifications.*.content'     => ['required', 'string'],
            'notifications.*.priority'    => ['sometimes', 'string', 'in:high,normal,low'],
            'notifications.*.metadata'    => ['sometimes', 'array'],
            'notifications.*.scheduled_at'=> ['sometimes', 'nullable', 'date'],
        ];
    }
}
