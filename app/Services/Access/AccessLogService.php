<?php

namespace App\Services\Access;

use App\Models\AccessLog;

class AccessLogService
{
    public function logAttempt(array $requestPayload, array $decision): AccessLog
    {
        /** @var \App\Models\AccessPass|null $accessPass */
        $accessPass = $decision['access_pass'] ?? null;
        /** @var \App\Models\AccessZone|null $accessZone */
        $accessZone = $decision['access_zone'] ?? null;

        return AccessLog::create([
            'access_pass_id'   => $accessPass?->id,
            'access_zone_id'   => $accessZone?->id,
            'vmserver_user_id' => $accessPass?->vmserver_user_id,
            'dni'              => $accessPass?->dni,
            'direction'        => $requestPayload['direction'] ?? 'in',
            'allowed'          => $decision['allowed'],
            'reason'           => $decision['reason'],
            'scanner_device_id' => $requestPayload['scanner_device_id'] ?? null,
            'scanned_by'       => $requestPayload['scanned_by'] ?? null,
            'request_payload'  => $this->sanitizePayload($requestPayload),
            'decision_payload' => $decision['decision_payload'] ?? null,
            'scanned_at'       => now(),
        ]);
    }

    private function sanitizePayload(array $payload): array
    {
        // Never store tokens or sensitive headers in logs
        $sensitiveKeys = ['token', 'password', 'secret', 'key', 'authorization'];

        return array_filter(
            $payload,
            fn ($key) => ! in_array(strtolower((string) $key), $sensitiveKeys, true),
            ARRAY_FILTER_USE_KEY
        );
    }
}
