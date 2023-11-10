<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        \App\Models\Location::create([
            'name' => 'Thessaloniki',
            'lat' => 40.64,
            'lon' => 22.93,
        ]);

        \App\Models\DataProvider::create([
            'name' => 'OpenMeteo',
            'url' => 'http://api.open-meteo.com/v1/forecast',
            'active' => 1,
            'request_method' => 'GET',
            'response_type' => 'JSON',
            'payload' => '{
                "latitude": "{$lat}",
                "longitude": "{$lon}",
                "hourly": "temperature_2m,apparent_temperature,precipitation_probability",
                "daily": "temperature_2m_max,temperature_2m_min,apparent_temperature_max,apparent_temperature_min,precipitation_sum,precipitation_probability_max",
                "timezone": "Europe/Moscow",
                "forecast_days": "1"
            }',
        ]);

        \App\Models\DataProvider::create([
            'name' => 'WeatherApi',
            'url' => 'http://api.weatherapi.com/v1/forecast.json',
            'active' => 1,
            'request_method' => 'GET',
            'response_type' => 'JSON',
            'payload' => '{
                "q": "{$lat},{$lon}",
                "key": "4da782a41477413c9c7152342230711",
                "days": "1",
                "aqi": "no",
                "alerts": "no"
            }',
        ]);
    }
}
