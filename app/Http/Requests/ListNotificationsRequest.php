<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListNotificationsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', 'in:pending,scheduled,queued,processing,sent,delivered,failed,canceled'],
            'channel' => ['sometimes', 'string', 'in:sms,email,push'],
            'priority' => ['sometimes', 'string', 'in:high,normal,low'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date', 'after_or_equal:from'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
