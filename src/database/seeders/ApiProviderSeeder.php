<?php

namespace Database\Seeders;

use App\Models\ApiProvider;
use Illuminate\Database\Seeder;

class ApiProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ApiProvider::firstOrCreate(
            ['name' => 'open-meteo'],
            [
                'description' => 'Бесплатный сервис прогноза погоды Open-Meteo API',
                'base_url' => 'https://api.open-meteo.com/v1',
                'status' => 'active',
                'credentials' => null,
            ]
        );
    }
}
