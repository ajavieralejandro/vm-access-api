<?php

namespace Tests\Feature;

use Tests\TestCase;

class AccessApiTest extends TestCase
{
    private string $internalKey = 'test-internal-key';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'integrations.internal_api_key' => $this->internalKey,
        ]);
    }

    public function test_public_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/health');

        $response
            ->assertOk()
            ->assertExactJson([
                'ok' => true,
                'service' => 'vm-access-api',
                'status' => 'healthy',
            ]);
    }

    public function test_internal_health_without_header_returns_unauthorized(): void
    {
        $response = $this->getJson('/api/internal/health');

        $response
            ->assertUnauthorized()
            ->assertExactJson([
                'ok' => false,
                'message' => 'Unauthorized internal request.',
            ]);
    }

    public function test_internal_health_with_incorrect_header_returns_unauthorized(): void
    {
        $response = $this->withHeaders([
            'X-Internal-Key' => 'wrong-key',
        ])->getJson('/api/internal/health');

        $response
            ->assertUnauthorized()
            ->assertExactJson([
                'ok' => false,
                'message' => 'Unauthorized internal request.',
            ]);
    }

    public function test_internal_health_with_valid_header_returns_ok(): void
    {
        $response = $this->withHeaders([
            'X-Internal-Key' => $this->internalKey,
        ])->getJson('/api/internal/health');

        $response
            ->assertOk()
            ->assertExactJson([
                'ok' => true,
                'service' => 'vm-access-api',
                'status' => 'internal_healthy',
            ]);
    }

    public function test_internal_access_validate_placeholder_returns_not_implemented(): void
    {
        $response = $this->withHeaders([
            'X-Internal-Key' => $this->internalKey,
        ])->postJson('/api/internal/access/validate');

        $response
            ->assertStatus(501)
            ->assertExactJson([
                'ok' => false,
                'message' => 'Access validation flow is not implemented yet.',
                'status' => 'not_implemented',
            ]);
    }
}
