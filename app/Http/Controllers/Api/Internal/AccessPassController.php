<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Services\Access\AccessPassService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AccessPassController extends Controller
{
    public function __construct(
        private readonly AccessPassService $accessPassService
    ) {}

    public function store(Request $request): JsonResponse
    {
        try {
            $pass = $this->accessPassService->create($request->all());

            $pass->load('accessZone');

            return ApiResponse::success([
                'access_pass' => [
                    'id'               => $pass->id,
                    'code'             => $pass->code,
                    'zone'             => $pass->accessZone->code,
                    'status'           => $pass->status->value,
                    'holder_name'      => $pass->holder_name,
                    'vmserver_user_id' => $pass->vmserver_user_id,
                    'dni'              => $pass->dni,
                    'source_service'   => $pass->source_service,
                    'source_type'      => $pass->source_type,
                    'source_reference' => $pass->source_reference,
                    'valid_from'       => $pass->valid_from?->toIso8601String(),
                    'valid_until'      => $pass->valid_until?->toIso8601String(),
                ],
            ], 201);
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }
}
