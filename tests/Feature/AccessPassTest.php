<?php

namespace Tests\Feature;

use App\Models\AccessZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessPassTest extends TestCase
{
    use RefreshDatabase;

    private string $internalKey = 'test-internal-key';

    protected function setUp(): void
    {
        parent::setUp();

        config(['integrations.internal_api_key' => $this->internalKey]);
    }

    private function withKey(): array
    {
        return ['X-Internal-Key' => $this->internalKey];
    }

    private function activeZone(string $code = 'pileta'): AccessZone
    {
        return AccessZone::create([
            'code'      => $code,
            'name'      => ucfirst($code),
            'is_active' => true,
        ]);
    }

    // -------------------------------------------------------------------------
    // 1. Rechaza crear pass sin X-Internal-Key
    // -------------------------------------------------------------------------
    public function test_rejects_create_pass_without_internal_key(): void
    {
        $this->activeZone();

        $response = $this->postJson('/api/internal/access-passes', [
            'zone' => 'pileta',
        ]);

        $response->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // 2. Crea pass con zona válida
    // -------------------------------------------------------------------------
    public function test_creates_pass_with_valid_zone(): void
    {
        $this->activeZone();

        $response = $this->withHeaders($this->withKey())
            ->postJson('/api/internal/access-passes', [
                'zone'        => 'pileta',
                'holder_name' => 'Juan Pérez',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('access_pass.zone', 'pileta')
            ->assertJsonPath('access_pass.status', 'active');

        $this->assertDatabaseHas('access_passes', ['holder_name' => 'Juan Pérez']);
    }

    // -------------------------------------------------------------------------
    // 3. Rechaza crear pass con zona inexistente
    // -------------------------------------------------------------------------
    public function test_rejects_create_pass_with_nonexistent_zone(): void
    {
        $response = $this->withHeaders($this->withKey())
            ->postJson('/api/internal/access-passes', [
                'zone' => 'nonexistent',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('ok', false);
    }

    // -------------------------------------------------------------------------
    // 4. Rechaza crear pass con zona inactiva
    // -------------------------------------------------------------------------
    public function test_rejects_create_pass_with_inactive_zone(): void
    {
        AccessZone::create([
            'code'      => 'gym',
            'name'      => 'Gimnasio',
            'is_active' => false,
        ]);

        $response = $this->withHeaders($this->withKey())
            ->postJson('/api/internal/access-passes', [
                'zone' => 'gym',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('ok', false);
    }

    // -------------------------------------------------------------------------
    // 5. Genera código único (formato AP_XXXX)
    // -------------------------------------------------------------------------
    public function test_generates_unique_code_for_each_pass(): void
    {
        $this->activeZone();

        $r1 = $this->withHeaders($this->withKey())
            ->postJson('/api/internal/access-passes', ['zone' => 'pileta']);

        $r2 = $this->withHeaders($this->withKey())
            ->postJson('/api/internal/access-passes', ['zone' => 'pileta']);

        $r1->assertStatus(201);
        $r2->assertStatus(201);

        $code1 = $r1->json('access_pass.code');
        $code2 = $r2->json('access_pass.code');

        $this->assertNotEquals($code1, $code2);
        $this->assertStringStartsWith('AP_', $code1);
        $this->assertStringStartsWith('AP_', $code2);
    }

    // -------------------------------------------------------------------------
    // 6. Guarda source_service, source_type, source_reference
    // -------------------------------------------------------------------------
    public function test_saves_source_fields(): void
    {
        $this->activeZone();

        $this->withHeaders($this->withKey())
            ->postJson('/api/internal/access-passes', [
                'zone'             => 'pileta',
                'source_service'   => 'piletas',
                'source_type'      => 'inscription',
                'source_reference' => '999',
            ]);

        $this->assertDatabaseHas('access_passes', [
            'source_service'   => 'piletas',
            'source_type'      => 'inscription',
            'source_reference' => '999',
        ]);
    }

    // -------------------------------------------------------------------------
    // 7. Respeta valid_from y valid_until
    // -------------------------------------------------------------------------
    public function test_respects_valid_from_and_valid_until(): void
    {
        $this->activeZone();

        $from  = now()->toIso8601String();
        $until = now()->addDays(7)->toIso8601String();

        $response = $this->withHeaders($this->withKey())
            ->postJson('/api/internal/access-passes', [
                'zone'        => 'pileta',
                'valid_from'  => $from,
                'valid_until' => $until,
            ]);

        $response->assertStatus(201);

        $pass = \App\Models\AccessPass::first();
        $this->assertNotNull($pass->valid_from);
        $this->assertNotNull($pass->valid_until);
    }
}
