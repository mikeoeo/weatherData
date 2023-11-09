<?php

namespace App\Services;

use App\Models\DataProvider;
use Illuminate\Support\Facades\Log;

class NormalizerService
{
    public function normalize(DataProvider $dataProvider, array $data)
    {
        if ($dataProvider->name === 'OpenMeteo') {
            return $this->normalizeOpenMeteo($data);
        } else if ($dataProvider->name === 'WeatherApi') {
            return $this->normalizeWeatherApi($data);
        }
        Log::critical('No normalizer found for data provider ' . $dataProvider->name);
        return false;
    }

    protected function normalizeOpenMeteo($data)
    {
        $normalizedData = [];
        if (isset($data['daily']['time'])) {
            $dailyData = $data['daily'];
            foreach ($dailyData['time'] as $slot => $day) {
                if (isset($dailyData['precipitation_sum'][$slot], $dailyData['precipitation_probability_max'][$slot])) {
                    $normalizedData['precipitation']['daily'][$slot] = [
                        'forecast_day' => $day,
                        'amount' => $dailyData['precipitation_sum'][$slot],
                        'percentage' => $dailyData['precipitation_probability_max'][$slot],
                    ];
                }
                if (isset($dailyData['time'], $dailyData['temperature_2m_min'], $dailyData['temperature_2m_max'], $dailyData['apparent_temperature_min'], $dailyData['apparent_temperature_max'])) {
                    $normalizedData['temperature']['daily'][$slot] = [
                        'forecast_day' => $day,
                        'temperature_min' => $dailyData['temperature_2m_min'][0],
                        'temperature_max' => $dailyData['temperature_2m_max'][0],
                        'temperature_avg' => null,
                        'apparent_temperature_min' => $dailyData['apparent_temperature_min'][0],
                        'apparent_temperature_max' => $dailyData['apparent_temperature_max'][0],
                        'apparent_temperature_avg' => null
                    ];
                }
            }
        }
        if (isset($data['hourly'])) {
            if (isset($data['hourly']['time'])) {
                $hourlyData = $data['hourly'];
                foreach ($hourlyData['time'] as $slot => $datetime) {
                    if (isset($hourlyData['precipitation_probability'][$slot])) {
                        $normalizedData['precipitation']['hourly'][$slot] = [
                            'forecast_datetime' => $datetime,
                            'amount' => null,
                            'percentage' => $hourlyData['precipitation_probability'][$slot],
                        ];
                    }
                    if (isset($hourlyData['temperature_2m'][$slot], $hourlyData['apparent_temperature'][$slot])) {
                        $normalizedData['temperature']['hourly'][$slot] = [
                            'forecast_datetime' => $datetime,
                            'temperature' => $hourlyData['temperature_2m'][$slot],
                            'temperature_felt' => $hourlyData['apparent_temperature'][$slot],
                        ];
                    }
                }
            }
        }

        return $normalizedData;
    }

    protected function normalizeWeatherApi($data)
    {
        $normalizedData = [];
        if (isset($data['forecast']['forecastday'])) {
            $dailyDataArray = $data['forecast']['forecastday'];
            foreach ($dailyDataArray as $dailyData) {
                $dailyDataPayload = $dailyData['day'];
                if (isset($dailyDataPayload['daily_chance_of_rain'], $dailyDataPayload['totalprecip_mm'])) {
                    $normalizedData['precipitation']['daily'][] = [
                        'forecast_day' => $dailyData['date'],
                        'amount' => $dailyDataPayload['totalprecip_mm'],
                        'percentage' => $dailyDataPayload['daily_chance_of_rain'],
                    ];
                }
                if (isset($dailyDataPayload['mintemp_c'], $dailyDataPayload['maxtemp_c'], $dailyDataPayload['avgtemp_c'])) {
                    $normalizedData['temperature']['daily'][] = [
                        'forecast_day' => $dailyData['date'],
                        'temperature_min' => $dailyDataPayload['mintemp_c'],
                        'temperature_max' => $dailyDataPayload['maxtemp_c'],
                        'temperature_avg' => $dailyDataPayload['avgtemp_c'],
                        'apparent_temperature_min' => null,
                        'apparent_temperature_max' => null,
                        'apparent_temperature_avg' => null
                    ];
                }
                $hourlyData = $dailyData['hour'];
                foreach ($hourlyData as $hourlyDataPayload) {
                    if (isset($hourlyDataPayload['time'], $hourlyDataPayload['precip_mm'], $hourlyDataPayload['chance_of_rain'])) {
                        $normalizedData['precipitation']['hourly'][] = [
                            'forecast_datetime' => $hourlyDataPayload['time'],
                            'amount' => $hourlyDataPayload['precip_mm'],
                            'percentage' => $hourlyDataPayload['chance_of_rain'],
                        ];
                    }
                    if (isset($hourlyDataPayload['temp_c'], $hourlyDataPayload['feelslike_c'])) {
                        $normalizedData['temperature']['hourly'][] = [
                            'forecast_datetime' => $hourlyDataPayload['time'],
                            'temperature' => $hourlyDataPayload['temp_c'],
                            'temperature_felt' => $hourlyDataPayload['feelslike_c'],
                        ];
                    }
                }
            }
        }
        return $normalizedData;
    }
}