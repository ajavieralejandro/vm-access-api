<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(array $data = [], int $status = 200): JsonResponse
    {
        return response()->json(array_merge(['ok' => true], $data), $status);
    }

    public static function error(string $message, int $status = 400, array $extra = []): JsonResponse
    {
        return response()->json(array_merge([
            'ok' => false,
            'message' => $message,
        ], $extra), $status);
    }

    public static function notImplemented(string $message): JsonResponse
    {
        return self::error($message, 501, ['status' => 'not_implemented']);
    }
}
