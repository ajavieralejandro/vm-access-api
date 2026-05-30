<?php

namespace App\Services\Access;

use App\Enums\AccessPassStatus;
use App\Models\AccessPass;
use App\Models\AccessZone;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AccessPassService
{
    public function create(array $payload): AccessPass
    {
        $zoneCode = $payload['zone'] ?? null;

        if (! $zoneCode) {
            throw new InvalidArgumentException('The zone field is required.');
        }

        $zone = AccessZone::where('code', $zoneCode)->first();

        if (! $zone) {
            throw new InvalidArgumentException("Access zone '{$zoneCode}' does not exist.");
        }

        if (! $zone->is_active) {
            throw new InvalidArgumentException("Access zone '{$zoneCode}' is not active.");
        }

        return AccessPass::create([
            'access_zone_id'   => $zone->id,
            'code'             => $this->generateUniqueCode(),
            'vmserver_user_id' => $payload['vmserver_user_id'] ?? null,
            'dni'              => $payload['dni'] ?? null,
            'holder_name'      => $payload['holder_name'] ?? null,
            'source_service'   => $payload['source_service'] ?? null,
            'source_type'      => $payload['source_type'] ?? null,
            'source_reference' => $payload['source_reference'] ?? null,
            'status'           => AccessPassStatus::Active->value,
            'valid_from'       => $payload['valid_from'] ?? now(),
            'valid_until'      => $payload['valid_until'] ?? null,
            'metadata'         => $payload['metadata'] ?? null,
        ]);
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = 'AP_' . strtoupper(Str::random(12));
        } while (AccessPass::where('code', $code)->exists());

        return $code;
    }
}
