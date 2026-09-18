<?php

namespace App\Services;

use App\Models\ApiProvider;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class WeatherApiService
{
    /**
     * Провайдер API, используемый сервисом.
     */
    protected ?ApiProvider $provider = null;

    /**
     * Конструктор сервиса.
     *
     * @param  ApiProvider|null  $provider  Опциональный экземпляр провайдера
     */
    public function __construct(?ApiProvider $provider = null)
    {
        $this->provider = $provider;
    }

    /**
     * Получение активного провайдера погоды из БД.
     *
     * @throws RuntimeException Если активный провайдер не найден
     */
    public function getProvider(): ApiProvider
    {
        if ($this->provider && $this->provider->isActive()) {
            return $this->provider;
        }

        $provider = ApiProvider::active()
            ->where(function ($query) {
                $query->where('name', 'open-meteo')
                      ->orWhere('name', 'weather')
                      ->orWhere('name', 'like', '%weather%');
            })
            ->first() ?? ApiProvider::active()->first();

        if (!$provider) {
            throw new RuntimeException('Активный провайдер погоды не найден в базе данных.', 503);
        }

        $this->provider = $provider;
        return $this->provider;
    }

    /**
     * Установка провайдера вручную (например, для тестов).
     */
    public function setProvider(ApiProvider $provider): self
    {
        $this->provider = $provider;
        return $this;
    }

    /**
     * Получение данных о погоде по названию города.
     *
     * @param  string  $city  Название города
     * @param  array  $options  Дополнительные параметры запроса
     * @return array  Данные о погоде
     *
     * @throws InvalidArgumentException При невалидном названии города
     * @throws RuntimeException При ошибке поиска города или внешнего API
     */
    public function getWeatherByCity(string $city, array $options = []): array
    {
        $city = trim($city);
        if (empty($city)) {
            throw new InvalidArgumentException('Название города обязательно для заполнения.');
        }

        $provider = $this->getProvider();

        // Геокодинг: поиск координат по названию города
        $geoUrl = 'https://geocoding-api.open-meteo.com/v1/search';
        $geoResponse = Http::timeout(10)->get($geoUrl, [
            'name' => $city,
            'count' => 1,
            'language' => 'ru',
            'format' => 'json',
        ]);

        if (!$geoResponse->successful()) {
            throw new RuntimeException('Не удалось связаться с сервисом геокодинга.', 502);
        }

        $geoData = $geoResponse->json();
        if (empty($geoData['results'][0])) {
            throw new RuntimeException("Город '{$city}' не найден.", 404);
        }

        $location = $geoData['results'][0];
        $latitude = $location['latitude'];
        $longitude = $location['longitude'];
        $resolvedCity = $location['name'];
        $country = $location['country'] ?? null;

        $weather = $this->getWeatherByCoordinates($latitude, $longitude, $options);

        return [
            'city' => $resolvedCity,
            'country' => $country,
            'provider' => $provider->name,
            ...$weather,
        ];
    }

    /**
     * Получение данных о погоде по координатам.
     *
     * @param  float|int|string  $latitude   Широта (-90 .. 90)
     * @param  float|int|string  $longitude  Долгота (-180 .. 180)
     * @param  array  $options  Дополнительные параметры запроса
     * @return array  Данные о погоде
     *
     * @throws InvalidArgumentException При невалидных координатах
     * @throws RuntimeException При ошибке внешнего API
     */
    public function getWeatherByCoordinates($latitude, $longitude, array $options = []): array
    {
        // Валидация входных параметров перед отправкой запроса к внешнему API
        $this->validateCoordinates($latitude, $longitude);

        $lat = (float) $latitude;
        $lon = (float) $longitude;

        $provider = $this->getProvider();
        $baseUrl = rtrim($provider->base_url, '/');

        // Формирование параметров запроса
        $params = array_merge([
            'latitude' => $lat,
            'longitude' => $lon,
            'current_weather' => 'true',
        ], $options);

        // Добавление учетных данных (если провайдер требует API-ключ в query)
        if (!empty($provider->credentials) && is_array($provider->credentials)) {
            if (isset($provider->credentials['api_key'])) {
                $params['appid'] = $provider->credentials['api_key'];
            }
        }

        $forecastUrl = str_ends_with($baseUrl, '/forecast') ? $baseUrl : "{$baseUrl}/forecast";

        $response = Http::timeout(10)->get($forecastUrl, $params);

        if (!$response->successful()) {
            throw new RuntimeException('Ошибка при обращении к внешнему API погоды.', 502);
        }

        $data = $response->json();

        return [
            'latitude' => $lat,
            'longitude' => $lon,
            'weather' => $data['current_weather'] ?? $data['current'] ?? $data,
            'raw' => $data,
        ];
    }

    /**
     * Валидация географических координат.
     *
     * @param  mixed  $latitude
     * @param  mixed  $longitude
     *
     * @throws InvalidArgumentException
     */
    protected function validateCoordinates($latitude, $longitude): void
    {
        if (!is_numeric($latitude)) {
            throw new InvalidArgumentException('Широта (latitude) должна быть числом.');
        }

        if (!is_numeric($longitude)) {
            throw new InvalidArgumentException('Долгота (longitude) должна быть числом.');
        }

        $lat = (float) $latitude;
        $lon = (float) $longitude;

        if ($lat < -90 || $lat > 90) {
            throw new InvalidArgumentException('Широта должна находиться в диапазоне от -90 до 90 градусов.');
        }

        if ($lon < -180 || $lon > 180) {
            throw new InvalidArgumentException('Долгота должна находиться в диапазоне от -180 до 180 градусов.');
        }
    }
}
