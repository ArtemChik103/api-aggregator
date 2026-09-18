<?php

namespace Tests\Feature;

use App\Models\ApiProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WeatherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаем дефолтного провайдера погоды для тестов
        ApiProvider::create([
            'name' => 'open-meteo',
            'base_url' => 'https://api.open-meteo.com/v1',
            'description' => 'Open-Meteo Weather API',
            'status' => 'active',
            'credentials' => null,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_weather_by_city(): void
    {
        $response = $this->getJson('/api/weather/city/Moscow');

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_access_weather_by_coordinates(): void
    {
        $response = $this->getJson('/api/weather/coordinates/55.75/37.61');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_get_weather_by_city(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Http::fake([
            'https://geocoding-api.open-meteo.com/v1/search*' => Http::response([
                'results' => [
                    [
                        'name' => 'Москва',
                        'latitude' => 55.7558,
                        'longitude' => 37.6173,
                        'country' => 'Россия',
                    ],
                ],
            ], 200),
            'https://api.open-meteo.com/v1/forecast*' => Http::response([
                'current_weather' => [
                    'temperature' => 18.5,
                    'windspeed' => 3.2,
                    'time' => '2026-09-12T12:00',
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/weather/city/Moscow');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'city' => 'Москва',
                    'country' => 'Россия',
                    'latitude' => 55.7558,
                    'longitude' => 37.6173,
                    'weather' => [
                        'temperature' => 18.5,
                        'windspeed' => 3.2,
                    ],
                ],
            ]);
    }

    public function test_authenticated_user_can_get_weather_by_coordinates(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Http::fake([
            'https://api.open-meteo.com/v1/forecast*' => Http::response([
                'current_weather' => [
                    'temperature' => 22.1,
                    'windspeed' => 5.0,
                    'time' => '2026-09-12T14:00',
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/weather/coordinates/55.75/37.61');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'latitude' => 55.75,
                    'longitude' => 37.61,
                    'weather' => [
                        'temperature' => 22.1,
                        'windspeed' => 5.0,
                    ],
                ],
            ]);
    }

    public function test_validation_fails_for_invalid_coordinates(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Широта выходит за пределы [-90, 90]
        $response = $this->getJson('/api/weather/coordinates/95.5/37.61');
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['latitude']);

        // Нечисловая долгота
        $response = $this->getJson('/api/weather/coordinates/55.75/invalid');
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['longitude']);
    }

    public function test_validation_fails_for_too_short_city(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/weather/city/a');
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['city']);
    }

    public function test_city_not_found_returns_404(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Http::fake([
            'https://geocoding-api.open-meteo.com/v1/search*' => Http::response([
                'results' => [],
            ], 200),
        ]);

        $response = $this->getJson('/api/weather/city/UnknownCity12345');
        $response->assertStatus(404)
            ->assertJson([
                'message' => "Город 'UnknownCity12345' не найден.",
            ]);
    }

    public function test_returns_503_when_no_active_provider_configured(): void
    {
        ApiProvider::query()->delete();

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/weather/coordinates/55.75/37.61');
        $response->assertStatus(503)
            ->assertJson([
                'message' => 'Активный провайдер погоды не найден в базе данных.',
            ]);
    }
}
