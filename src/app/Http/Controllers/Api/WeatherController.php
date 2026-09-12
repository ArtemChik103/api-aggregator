<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WeatherApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class WeatherController extends Controller
{
    /**
     * Сервис получения погоды.
     */
    protected WeatherApiService $weatherService;

    /**
     * Конструктор контроллера с внедрением зависимостей.
     */
    public function __construct(WeatherApiService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    /**
     * Получение погоды по названию города.
     *
     * @param  Request  $request
     * @param  string|null  $city
     * @return JsonResponse
     */
    public function getByCity(Request $request, ?string $city = null): JsonResponse
    {
        $cityName = $city ?? $request->route('city') ?? $request->input('city');

        $validator = Validator::make(
            ['city' => $cityName],
            [
                'city' => ['required', 'string', 'min:2', 'max:255', 'regex:/^[\pL\s\-\.\,\'\d]+$/u'],
            ],
            [
                'city.required' => 'Параметр city обязателен.',
                'city.min' => 'Название города должно содержать минимум 2 символа.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации входящих данных.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $weatherData = $this->weatherService->getWeatherByCity($cityName);

            return response()->json([
                'status' => 'success',
                'data' => $weatherData,
            ], 200);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (RuntimeException $e) {
            $statusCode = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 502;

            return response()->json([
                'message' => $e->getMessage(),
            ], $statusCode);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Произошла непредвиденная ошибка при получении данных о погоде.',
            ], 500);
        }
    }

    /**
     * Получение погоды по географическим координатам (широта и долгота).
     *
     * @param  Request  $request
     * @param  mixed  $latitude
     * @param  mixed  $longitude
     * @return JsonResponse
     */
    public function getByCoordinates(Request $request, $latitude = null, $longitude = null): JsonResponse
    {
        $lat = $latitude ?? $request->route('latitude') ?? $request->input('latitude');
        $lon = $longitude ?? $request->route('longitude') ?? $request->input('longitude');

        $validator = Validator::make(
            [
                'latitude' => $lat,
                'longitude' => $lon,
            ],
            [
                'latitude' => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
            ],
            [
                'latitude.required' => 'Параметр latitude обязателен.',
                'latitude.numeric' => 'Широта (latitude) должна быть числом.',
                'latitude.between' => 'Широта должна быть в диапазоне от -90 до 90 градусов.',
                'longitude.required' => 'Параметр longitude обязателен.',
                'longitude.numeric' => 'Долгота (longitude) должна быть числом.',
                'longitude.between' => 'Долгота должна быть в диапазоне от -180 до 180 градусов.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации входящих данных.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $weatherData = $this->weatherService->getWeatherByCoordinates($lat, $lon);

            return response()->json([
                'status' => 'success',
                'data' => $weatherData,
            ], 200);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (RuntimeException $e) {
            $statusCode = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 502;

            return response()->json([
                'message' => $e->getMessage(),
            ], $statusCode);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Произошла непредвиденная ошибка при получении данных о погоде.',
            ], 500);
        }
    }
}
