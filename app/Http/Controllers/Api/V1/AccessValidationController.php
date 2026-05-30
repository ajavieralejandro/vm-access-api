<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Access\AccessDecisionService;
use App\Services\Access\AccessLogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AccessValidationController extends Controller
{
    public function __construct(
        private readonly AccessDecisionService $decisionService,
        private readonly AccessLogService $logService
    ) {}

    public function validate(Request $request): JsonResponse
    {
        $payload = $request->only([
            'code',
            'zone',
            'direction',
            'scanner_device_id',
            'scanned_by',
        ]);

        if (empty($payload['code']) && ! array_key_exists('code', $payload)) {
            return ApiResponse::error('The code field is required.', 422);
        }

        try {
            $decision = $this->decisionService->validate($payload);

            $this->logService->logAttempt($payload, $decision);

            $response = [
                'allowed'  => $decision['allowed'],
                'reason'   => $decision['reason'],
                'message'  => $decision['message'],
            ];

            if ($decision['allowed']) {
                $pass = $decision['access_pass'];
                $response['access'] = [
                    'zone'        => $decision['access_zone']->code,
                    'holder_name' => $pass->holder_name,
                    'direction'   => $payload['direction'] ?? 'in',
                ];
            }

            return ApiResponse::success($response);
        } catch (Throwable $e) {
            return ApiResponse::error('Validation could not be processed.', 422, [
                'status' => 'validation_error',
            ]);
        }
    }
}
