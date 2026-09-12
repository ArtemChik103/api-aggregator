<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    /**
     * Возвращает статус приложения, текущее время сервера (ISO 8601) и версию API.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'OK',
            'timestamp' => now()->toIso8601ZuluString(),
            'api_version' => config('app.version', '1.0.0'),
        ]);
    }
}
