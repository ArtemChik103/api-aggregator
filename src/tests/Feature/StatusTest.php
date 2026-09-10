<?php

namespace Tests\Feature;

use Tests\TestCase;

class StatusTest extends TestCase
{
    public function test_status_endpoint_returns_correct_structure(): void
    {
        $response = $this->getJson('/api/status');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'api_version',
            ])
            ->assertJson([
                'status' => 'OK',
                'api_version' => '1.0.0',
            ]);
    }
}
