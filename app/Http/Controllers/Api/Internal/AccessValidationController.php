<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class AccessValidationController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return ApiResponse::notImplemented('Access validation flow is not implemented yet.');
    }
}
