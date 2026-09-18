<?php

namespace Tests\Feature;

use App\Models\ApiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_provider_via_artisan_options(): void
    {
        $this->artisan('provider:create', [
            '--name' => 'test-weather-api',
            '--base_url' => 'https://api.test-weather.com/v1',
            '--description' => 'Test weather API description',
            '--status' => 'active',
            '--credentials' => json_encode(['api_key' => 'secret-12345']),
            '--no-interaction' => true,
        ])
            ->assertExitCode(0);

        $this->assertDatabaseHas('api_providers', [
            'name' => 'test-weather-api',
            'base_url' => 'https://api.test-weather.com/v1',
            'status' => 'active',
        ]);

        $provider = ApiProvider::where('name', 'test-weather-api')->first();
        $this->assertNotNull($provider);
        $this->assertEquals(['api_key' => 'secret-12345'], $provider->credentials);
        $this->assertTrue($provider->isActive());
    }

    public function test_fails_validation_for_invalid_url(): void
    {
        $this->artisan('provider:create', [
            '--name' => 'bad-url-provider',
            '--base_url' => 'not-a-valid-url',
            '--no-interaction' => true,
        ])
            ->assertExitCode(1);

        $this->assertDatabaseMissing('api_providers', [
            'name' => 'bad-url-provider',
        ]);
    }

    public function test_fails_validation_for_duplicate_name(): void
    {
        ApiProvider::create([
            'name' => 'existing-provider',
            'base_url' => 'https://api.existing.com',
            'status' => 'active',
        ]);

        $this->artisan('provider:create', [
            '--name' => 'existing-provider',
            '--base_url' => 'https://api.another.com',
            '--no-interaction' => true,
        ])
            ->assertExitCode(1);
    }
}
