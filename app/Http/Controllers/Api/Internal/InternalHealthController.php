<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class InternalHealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'service' => 'vm-access-api',
            'status' => 'internal_healthy',
        ]);
    }
}
