<?php

namespace App\Services;

use App\Models\Location;
use App\Models\DataProvider;
use App\Services\NormalizerService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class WeatherForecastService
{
    protected NormalizerService $normalizerService;
    protected ModelValidator $modelValidator;
    protected string $jobLogMessage;

    public function __construct(NormalizerService $normalizerService, ModelValidator $modelValidator)
    {
        $this->normalizerService = $normalizerService;
        $this->modelValidator = $modelValidator;
        $this->jobLogMessage = '';
    }

    /**
     * @param int
     * @param int
     */
    public function fetchAllData($dataProviderId = 0, $locationId = 0)
    {
        $dataProviders = $this->getDataProviders($dataProviderId);
        $locations = $this->getLocations($locationId);
        if (empty($dataProviders) || empty($locations)) {
            $this->logJobStatus(0, 0, 0, 'No data providers or locations');
            return false;
        }

        foreach ($dataProviders as $dataProvider)
        {
            if (!$this->modelValidator->isValid(DataProvider::class, $dataProvider)) {
                $this->logJobStatus(0, $dataProvider->id, 0, 'Data provider not valid');
                continue;
            }
            foreach ($locations as $location)
            {
                if (!$this->modelValidator->isValid(Location::class, $location)) {
                    $this->logJobStatus(0, $dataProvider->id, $location->id, 'Location not valid');
                    continue;
                }
                $data = $this->fetchData($dataProvider, $location);
                if ($data === false) {
                    $this->logJobStatus(0, $dataProvider->id, $location->id, $this->jobLogMessage);
                    continue;
                }
                
                $this->jobLogMessage = '';
                $normalizedData = $this->normalizerService->normalize($dataProvider, $data);
                if ($normalizedData === false) {
                    $this->logJobStatus(0, $dataProvider->id, $location->id, 'No normalizer found for data provider ' . $dataProvider->name);
                    continue;
                }

                $foundData = $this->savePrecipitationDailyData($normalizedData, $dataProvider->id, $location->id);
                $foundData &= $this->savePrecipitationHourlyData($normalizedData, $dataProvider->id, $location->id);
                $foundData &= $this->saveTemperatureDailyData($normalizedData, $dataProvider->id, $location->id);
                $foundData &= $this->saveTemperatureHourlyData($normalizedData, $dataProvider->id, $location->id);

                $this->logJobStatus($foundData, $dataProvider->id, $location->id, $this->jobLogMessage);
            }
        }
    }

    protected function getDataProviders($dataProviderId)
    {
        $dataProviders = [];
        if ($dataProviderId == 0) {
            $dataProviders = DataProvider::where('active', 1)->get();
            if (empty($dataProviders)) {
                Log::critical('No active data provider found');
                return false;
            }
        } else {
            $dataProviders[] = DataProvider::find($dataProviderId);
            if (empty($dataProviders)) {
                Log::critical('No data provider found with id ' . $dataProviderId);
                return false;
            }
        }
        return $dataProviders;
    }

    protected function getLocations($locationId)
    {
        $locations = [];
        if ($locationId == 0) {
            $locations = Location::all();
            if (empty($locations)) {
                Log::critical('No locations found');
                return false;
            }
        } else {
            $locations[] = Location::find($locationId);
            if (empty($locations)) {
                Log::critical('No location found with id ' . $locationId);
                return false;
            }
        }
        return $locations;
    }

    protected function fetchData($dataProvider, $location)
    {
        $providerUrl = $dataProvider->url;
        $latLonPart = $dataProvider->lat_lon_format;
        $lat = $location->lat;
        $lon = $location->lon;

        $latLonPart = str_replace(['{$lat}', '{$lon}'], [$lat, $lon], $latLonPart);
        $url = $providerUrl . '&' . $latLonPart;
        $response = Http::get($url);
        if ($response->successful()) {
            return $response->json();
        } else {
            $this->jobLogMessage = 'Could not fetch data from url ' . $url;
            Log::warning($this->jobLogMessage);
            return false;
        }
    }

    protected function logJobStatus($status, $dataProviderId, $locationId, $message = '')
    {
        \App\Models\JobStatus::create([
            'location_id' => $locationId,
            'data_providers_id' => $dataProviderId,
            'success_status' => $status,
            'message' => $message,
        ]);
    }

    protected function savePrecipitationDailyData($normalizedData, $dataProviderId, $locationId)
    {
        $foundData = false;
        if (isset($normalizedData['precipitation']['daily'])) {
            foreach ($normalizedData['precipitation']['daily'] as $precipitationData)
            {
                $precipitationDaily = \App\Models\PrecipitationDailyForecast::firstOrNew([
                    'location_id' => $locationId,
                    'data_providers_id' => $dataProviderId,
                    'forecast_day' => $precipitationData['forecast_day'],
                ]);
                $precipitationDaily->amount = $precipitationData['amount'];
                $precipitationDaily->percentage = $precipitationData['percentage'];
                $precipitationDaily->save();
                $foundData = true;
            }
        }
        if ($foundData == false) {
            $this->jobLogMessage .= 'No daily precipitation data for location ' . $locationId . ' from provider ' . $dataProviderId . '. ';
            Log::warning($this->jobLogMessage);
        }
        return $foundData;
    }

    protected function savePrecipitationHourlyData($normalizedData, $dataProviderId, $locationId)
    {
        $foundData = false;
        if (isset($normalizedData['precipitation']['hourly'])) {
            foreach ($normalizedData['precipitation']['hourly'] as $precipitationData)
            {
                $precipitationHourly = \App\Models\PrecipitationHourlyForecast::firstOrNew([
                    'location_id' => $locationId,
                    'data_providers_id' => $dataProviderId,
                    'forecast_datetime' => $precipitationData['forecast_datetime'],
                ]);
                $precipitationHourly->amount = $precipitationData['amount'];
                $precipitationHourly->percentage = $precipitationData['percentage'];
                $precipitationHourly->save();
                $foundData = true;
            }
        }
        if ($foundData == false) {
            $this->jobLogMessage .= 'No hourly precipitation data for location ' . $locationId . ' from provider ' . $dataProviderId . '. ';
            Log::warning($this->jobLogMessage);
        }
        return $foundData;
    }

    protected function saveTemperatureDailyData($normalizedData, $dataProviderId, $locationId)
    {
        $foundData = false;
        if (isset($normalizedData['temperature']['daily'])) {
            foreach ($normalizedData['temperature']['daily'] as $temperatureData)
            {
                $temperatureDaily = \App\Models\TemperatureDailyForecast::firstOrNew([
                    'location_id' => $locationId,
                    'data_providers_id' => $dataProviderId,
                    'forecast_day' => $temperatureData['forecast_day'],
                ]);
                $temperatureDaily->temperature_min = isset($temperatureData['temperature_min']) ? $temperatureData['temperature_min'] : null;
                $temperatureDaily->temperature_max = isset($temperatureData['temperature_max']) ? $temperatureData['temperature_max'] : null;
                $temperatureDaily->temperature_avg = isset($temperatureData['temperature_avg']) ? $temperatureData['temperature_avg'] : null;
                $temperatureDaily->apparent_temperature_min = isset($temperatureData['apparent_temperature_min']) ? $temperatureData['apparent_temperature_min'] : null;
                $temperatureDaily->apparent_temperature_max = isset($temperatureData['apparent_temperature_max']) ? $temperatureData['apparent_temperature_max'] : null;
                $temperatureDaily->apparent_temperature_avg = isset($temperatureData['apparent_temperature_avg']) ? $temperatureData['apparent_temperature_avg'] : null;
                $temperatureDaily->save();
                $foundData = true;
            }
        }
        if ($foundData == false) {
            $this->jobLogMessage .= 'No daily temperature data for location ' . $locationId . ' from provider ' . $dataProviderId . '. ';
            Log::warning($this->jobLogMessage);
        }
        return $foundData;
    }

    protected function saveTemperatureHourlyData($normalizedData, $dataProviderId, $locationId)
    {
        $foundData = false;
        if (isset($normalizedData['temperature']['hourly'])) {
            foreach ($normalizedData['temperature']['hourly'] as $temperatureData)
            {
                $temperatureHourly = \App\Models\TemperatureHourlyForecast::firstOrNew([
                    'location_id' => $locationId,
                    'data_providers_id' => $dataProviderId,
                    'forecast_datetime' => $temperatureData['forecast_datetime'],
                ]);
                $temperatureHourly->temperature = $temperatureData['temperature'];
                $temperatureHourly->temperature_felt = $temperatureData['temperature_felt'];
                $temperatureHourly->save();
                $foundData = true;
            }
        }
        if ($foundData == false) {
            $this->jobLogMessage .= 'No hourly temperature data for location ' . $locationId . ' from provider ' . $dataProviderId . '. ';
            Log::warning($this->jobLogMessage);
        }
        return $foundData;
    }
}