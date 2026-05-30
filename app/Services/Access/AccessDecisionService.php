<?php

namespace App\Services\Access;

use App\Enums\AccessDecisionReason;
use App\Enums\AccessPassStatus;
use App\Models\AccessPass;
use App\Models\AccessZone;

class AccessDecisionService
{
    public function validate(array $payload): array
    {
        $code      = $payload['code'] ?? null;
        $zoneCode  = $payload['zone'] ?? null;

        // 1. Missing code
        if (! $code) {
            return $this->deny(AccessDecisionReason::InvalidQr, null, null, $payload);
        }

        // 2. Pass not found
        $pass = AccessPass::forCode($code)->first();

        if (! $pass) {
            return $this->deny(AccessDecisionReason::InvalidQr, null, null, $payload);
        }

        // 3. Missing zone
        if (! $zoneCode) {
            return $this->deny(AccessDecisionReason::MissingZone, $pass, null, $payload);
        }

        // 4. Zone does not exist
        $zone = AccessZone::where('code', $zoneCode)->first();

        if (! $zone) {
            return $this->deny(AccessDecisionReason::InvalidZone, $pass, null, $payload);
        }

        // 5. Zone inactive
        if (! $zone->is_active) {
            return $this->deny(AccessDecisionReason::InactiveZone, $pass, $zone, $payload);
        }

        // 6. Pass belongs to a different zone
        if ($pass->access_zone_id !== $zone->id) {
            return $this->deny(AccessDecisionReason::InvalidZone, $pass, $zone, $payload);
        }

        // 7-9. Pass status checks
        if ($pass->status === AccessPassStatus::Revoked) {
            return $this->deny(AccessDecisionReason::RevokedPass, $pass, $zone, $payload);
        }

        if ($pass->status === AccessPassStatus::Used) {
            return $this->deny(AccessDecisionReason::UsedPass, $pass, $zone, $payload);
        }

        if ($pass->status === AccessPassStatus::Expired) {
            return $this->deny(AccessDecisionReason::ExpiredPass, $pass, $zone, $payload);
        }

        // 10. Not yet valid
        if ($pass->valid_from && $pass->valid_from->isFuture()) {
            return $this->deny(AccessDecisionReason::NotYetValid, $pass, $zone, $payload);
        }

        // 11. Expired by valid_until
        if ($pass->valid_until && $pass->valid_until->isPast()) {
            return $this->deny(AccessDecisionReason::ExpiredPass, $pass, $zone, $payload);
        }

        // 12. All checks passed — allow
        return $this->allow($pass, $zone, $payload);
    }

    private function allow(AccessPass $pass, AccessZone $zone, array $payload): array
    {
        return [
            'allowed'          => true,
            'reason'           => AccessDecisionReason::Ok->value,
            'message'          => 'Access allowed.',
            'access_pass'      => $pass,
            'access_zone'      => $zone,
            'decision_payload' => [
                'code'      => $pass->code,
                'zone'      => $zone->code,
                'status'    => $pass->status->value,
                'valid_from' => $pass->valid_from?->toIso8601String(),
                'valid_until' => $pass->valid_until?->toIso8601String(),
            ],
        ];
    }

    private function deny(
        AccessDecisionReason $reason,
        ?AccessPass $pass,
        ?AccessZone $zone,
        array $payload
    ): array {
        return [
            'allowed'          => false,
            'reason'           => $reason->value,
            'message'          => 'Access denied.',
            'access_pass'      => $pass,
            'access_zone'      => $zone,
            'decision_payload' => [
                'code'   => $payload['code'] ?? null,
                'zone'   => $payload['zone'] ?? null,
                'reason' => $reason->value,
            ],
        ];
    }
}
