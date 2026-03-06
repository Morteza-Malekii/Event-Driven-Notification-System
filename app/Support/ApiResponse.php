<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(mixed $data, int $status = 200, ?array $pagination = null): JsonResponse
    {
        $payload = ['success' => true, 'data' => $data];

        if ($pagination !== null) {
            $payload['meta'] = ['pagination' => $pagination];
        }

        return response()->json($payload, $status);
    }

    public static function error(string $code, string $message, int $status, mixed $details = null): JsonResponse
    {
        $payload = [
            'error' => [
                'code'    => $code,
                'message' => $message,
            ],
        ];

        if ($details !== null) {
            $payload['error']['details'] = $details;
        }

        return response()->json($payload, $status);
    }
}
