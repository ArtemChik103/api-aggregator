<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\WeatherController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/status', [StatusController::class, 'index']);

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/token', [AuthController::class, 'newToken']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::prefix('weather')->group(function () {
        Route::get('/city/{city}', [WeatherController::class, 'getByCity']);
        Route::get('/coordinates/{latitude}/{longitude}', [WeatherController::class, 'getByCoordinates']);
    });
});
