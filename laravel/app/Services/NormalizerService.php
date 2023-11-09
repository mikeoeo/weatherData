<?php

namespace App\Services;

use App\Models\DataProvider;
use Illuminate\Support\Facades\Log;

class NormalizerService
{
    /**
     * Normalize fetched data in order for them to be ready for db storing. 
     * The first level key should be 'precipitation' or 'temperature'.
     * The second level key should be 'daily' or 'hourly'.
     * The third level key should be an auto increment number.
     * The forth level keys for precipitation daily are 'forecast_day', 'amount' and 'percentage', 
     * for the precipitation hourly are 'forecast_datetime', 'amount' and 'percentage', 
     * for the temperature daily are 'forecast_day', 'temperature_min', 'temperature_max', 'temperature_avg', 'apparent_temperature_min', 'apparent_temperature_max' and 'apparent_temperature_avg'
     * and for the temperature hourly are 'forecast_datetime', 'temperature' and 'temperature_felt'.
     * 
     * In case some of the fourth level data do not exist, we need to set their value to null, like in the following example:
     * 
     * $normalizedData['precipitation']['hourly'][0] = [
     *      'forecast_datetime' => '2023-11-09 00:00',
     *      'amount' => null,
     *      'percentage' => 10.0
     * ];
     * @param DataProvider $dataProvider
     * @param array $data
     */
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

    /**
     * Normalized data fetched from Open Meteo
     * @param array $data the fetched data
     * @return array the normalized data
     */
    protected function normalizeOpenMeteo(array $data)
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

    /**
     * Normalized data fetched from Weather API
     * @param array $data the fetched data
     * @return array the normalized data
     */
    protected function normalizeWeatherApi(array $data)
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