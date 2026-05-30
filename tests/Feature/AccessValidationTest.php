<?php

namespace Tests\Feature;

use App\Enums\AccessPassStatus;
use App\Models\AccessLog;
use App\Models\AccessPass;
use App\Models\AccessZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessValidationTest extends TestCase
{
    use RefreshDatabase;

    private AccessZone $zone;

    protected function setUp(): void
    {
        parent::setUp();

        $this->zone = AccessZone::create([
            'code'      => 'pileta',
            'name'      => 'Pileta',
            'is_active' => true,
        ]);
    }

    private function makePass(array $overrides = []): AccessPass
    {
        return AccessPass::create(array_merge([
            'access_zone_id' => $this->zone->id,
            'code'           => 'AP_TESTCODE001',
            'status'         => AccessPassStatus::Active->value,
            'valid_from'     => now()->subMinute(),
            'valid_until'    => now()->addDay(),
        ], $overrides));
    }

    private function validate(array $payload): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/v1/access/validate', $payload);
    }

    // -------------------------------------------------------------------------
    // 1. Permite pass activo y vigente
    // -------------------------------------------------------------------------
    public function test_allows_active_valid_pass(): void
    {
        $this->makePass();

        $response = $this->validate([
            'code'      => 'AP_TESTCODE001',
            'zone'      => 'pileta',
            'direction' => 'in',
        ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('allowed', true)
            ->assertJsonPath('reason', 'ok')
            ->assertJsonPath('access.zone', 'pileta');
    }

    // -------------------------------------------------------------------------
    // 2. Rechaza QR/código inexistente
    // -------------------------------------------------------------------------
    public function test_rejects_nonexistent_code(): void
    {
        $response = $this->validate([
            'code' => 'AP_DOESNOTEXIST',
            'zone' => 'pileta',
        ]);

        $response->assertOk()
            ->assertJsonPath('allowed', false)
            ->assertJsonPath('reason', 'invalid_qr');
    }

    // -------------------------------------------------------------------------
    // 3. Rechaza pass vencido por valid_until
    // -------------------------------------------------------------------------
    public function test_rejects_expired_pass_by_valid_until(): void
    {
        $this->makePass([
            'valid_from'  => now()->subDays(2),
            'valid_until' => now()->subDay(),
        ]);

        $response = $this->validate([
            'code' => 'AP_TESTCODE001',
            'zone' => 'pileta',
        ]);

        $response->assertOk()
            ->assertJsonPath('allowed', false)
            ->assertJsonPath('reason', 'expired_pass');
    }

    // -------------------------------------------------------------------------
    // 4. Rechaza pass con status revoked
    // -------------------------------------------------------------------------
    public function test_rejects_revoked_pass(): void
    {
        $this->makePass([
            'status'     => AccessPassStatus::Revoked->value,
            'revoked_at' => now(),
        ]);

        $response = $this->validate([
            'code' => 'AP_TESTCODE001',
            'zone' => 'pileta',
        ]);

        $response->assertOk()
            ->assertJsonPath('allowed', false)
            ->assertJsonPath('reason', 'revoked_pass');
    }

    // -------------------------------------------------------------------------
    // 5. Rechaza pass con status used
    // -------------------------------------------------------------------------
    public function test_rejects_used_pass(): void
    {
        $this->makePass([
            'status'  => AccessPassStatus::Used->value,
            'used_at' => now(),
        ]);

        $response = $this->validate([
            'code' => 'AP_TESTCODE001',
            'zone' => 'pileta',
        ]);

        $response->assertOk()
            ->assertJsonPath('allowed', false)
            ->assertJsonPath('reason', 'used_pass');
    }

    // -------------------------------------------------------------------------
    // 6. Rechaza zona inexistente
    // -------------------------------------------------------------------------
    public function test_rejects_nonexistent_zone(): void
    {
        $this->makePass();

        $response = $this->validate([
            'code' => 'AP_TESTCODE001',
            'zone' => 'nowhere',
        ]);

        $response->assertOk()
            ->assertJsonPath('allowed', false)
            ->assertJsonPath('reason', 'invalid_zone');
    }

    // -------------------------------------------------------------------------
    // 7. Rechaza zona inactiva
    // -------------------------------------------------------------------------
    public function test_rejects_inactive_zone(): void
    {
        AccessZone::create([
            'code'      => 'gym',
            'name'      => 'Gimnasio',
            'is_active' => false,
        ]);

        // pass pertenece a gym (inactivo)
        $gymZone = AccessZone::where('code', 'gym')->first();
        AccessPass::create([
            'access_zone_id' => $gymZone->id,
            'code'           => 'AP_GYMPASS001',
            'status'         => AccessPassStatus::Active->value,
            'valid_from'     => now()->subMinute(),
        ]);

        $response = $this->validate([
            'code' => 'AP_GYMPASS001',
            'zone' => 'gym',
        ]);

        $response->assertOk()
            ->assertJsonPath('allowed', false)
            ->assertJsonPath('reason', 'inactive_zone');
    }

    // -------------------------------------------------------------------------
    // 8. Rechaza zona incorrecta (pass de pileta validado en gym)
    // -------------------------------------------------------------------------
    public function test_rejects_wrong_zone(): void
    {
        AccessZone::create([
            'code'      => 'gym',
            'name'      => 'Gimnasio',
            'is_active' => true,
        ]);

        $this->makePass(); // pertenece a 'pileta'

        $response = $this->validate([
            'code' => 'AP_TESTCODE001',
            'zone' => 'gym', // zona incorrecta
        ]);

        $response->assertOk()
            ->assertJsonPath('allowed', false)
            ->assertJsonPath('reason', 'invalid_zone');
    }

    // -------------------------------------------------------------------------
    // 9. Rechaza pass que todavía no está vigente
    // -------------------------------------------------------------------------
    public function test_rejects_not_yet_valid_pass(): void
    {
        $this->makePass([
            'valid_from' => now()->addHour(),
        ]);

        $response = $this->validate([
            'code' => 'AP_TESTCODE001',
            'zone' => 'pileta',
        ]);

        $response->assertOk()
            ->assertJsonPath('allowed', false)
            ->assertJsonPath('reason', 'not_yet_valid');
    }

    // -------------------------------------------------------------------------
    // 10. Registra access_log cuando permite
    // -------------------------------------------------------------------------
    public function test_logs_access_when_allowed(): void
    {
        $this->makePass();

        $this->validate([
            'code'      => 'AP_TESTCODE001',
            'zone'      => 'pileta',
            'direction' => 'in',
        ]);

        $this->assertDatabaseHas('access_logs', [
            'allowed' => 1,
            'reason'  => 'ok',
        ]);
    }

    // -------------------------------------------------------------------------
    // 11. Registra access_log cuando rechaza
    // -------------------------------------------------------------------------
    public function test_logs_access_when_denied(): void
    {
        $this->validate([
            'code' => 'AP_DOESNOTEXIST',
            'zone' => 'pileta',
        ]);

        $this->assertDatabaseHas('access_logs', [
            'allowed' => 0,
            'reason'  => 'invalid_qr',
        ]);
    }

    // -------------------------------------------------------------------------
    // 12. Guarda scanner_device_id y direction en el log
    // -------------------------------------------------------------------------
    public function test_saves_scanner_device_id_and_direction_in_log(): void
    {
        $this->makePass();

        $this->validate([
            'code'              => 'AP_TESTCODE001',
            'zone'              => 'pileta',
            'direction'         => 'out',
            'scanner_device_id' => 'scanner-01',
        ]);

        $log = AccessLog::first();

        $this->assertEquals('scanner-01', $log->scanner_device_id);
        $this->assertEquals('out', $log->direction);
    }
}
