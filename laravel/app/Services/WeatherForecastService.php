<?php

namespace App\Services;

use App\Models\Location;
use App\Models\DataProvider;
use App\Services\NormalizerService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class WeatherForecastService
{
    /**
     * A service that allows us to normalize whatever data we get from the API endpoints
     * @var NormalizerService
     */
    protected $normalizerService;
    
    /**
     * A service that allows us to validate some of our models before we use them
     * @var ModelValidator
     */
    protected $modelValidator;
    
    /**
     * A string message that allows us to get a message accross from a function to our logs
     * @var string
     */
    protected $jobLogMessage;

    /**
     * @param NormalizerService $normalizerService
     * @param ModelValidator $modelValidator
     */
    public function __construct(NormalizerService $normalizerService, ModelValidator $modelValidator)
    {
        $this->normalizerService = $normalizerService;
        $this->modelValidator = $modelValidator;
        $this->jobLogMessage = '';
    }

    /**
     * Fetch and store data from the given (or all) providers for the given (or all) locations
     * @param int $dataProviderId id of the DataProvider or 0 for all active providers
     * @param int $locationId id of the Location or 0 for all locations
     * @return bool
     */
    public function fetchAllData(int $dataProviderId = 0, int $locationId = 0): bool
    {
        $dataProviders = $this->getDataProviders($dataProviderId);
        $locations = $this->getLocations($locationId);
        if (empty($dataProviders) || empty($locations)) {
            $this->logJob(0, null, null, 'No data providers or locations');
            return false;
        }

        foreach ($dataProviders as $dataProvider)
        {
            if (!$this->modelValidator->isValid(DataProvider::class, $dataProvider)) {
                $this->logJob(0, $dataProvider->id, null, 'Data provider not valid');
                continue;
            }
            foreach ($locations as $location)
            {
                if (!$this->modelValidator->isValid(Location::class, $location)) {
                    $this->logJob(0, $dataProvider->id, $location->id, 'Location not valid');
                    continue;
                }
                $data = $this->fetchData($dataProvider, $location);
                if (empty($data)) {
                    $this->logJob(0, $dataProvider->id, $location->id, $this->jobLogMessage);
                    continue;
                }
                
                $this->jobLogMessage = '';
                $normalizedData = $this->normalizerService->normalize($dataProvider->name, $data);
                if ($normalizedData === false) {
                    $this->logJob(0, $dataProvider->id, $location->id, 'No normalizer found for data provider ' . $dataProvider->name);
                    continue;
                }

                $foundData = $this->savePrecipitationDailyData($normalizedData, $dataProvider->id, $location->id);
                $foundData &= $this->savePrecipitationHourlyData($normalizedData, $dataProvider->id, $location->id);
                $foundData &= $this->saveTemperatureDailyData($normalizedData, $dataProvider->id, $location->id);
                $foundData &= $this->saveTemperatureHourlyData($normalizedData, $dataProvider->id, $location->id);

                $this->logJob($foundData, $dataProvider->id, $location->id, $this->jobLogMessage);
            }
        }
        return true;
    }

    /**
     * Get data providers that will be used
     * @param int $dataProviderId DataProvider id
     */
    protected function getDataProviders(int $dataProviderId)
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

    /**
     * Get locations that will be used
     * @param int $locationId Location id
     */
    protected function getLocations(int $locationId)
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

    /**
     * Fetch data from a provider for a location or false on error
     * @param DataProvider $dataProvider
     * @param Location $location
     * @return array|bool
     */
    protected function fetchData(DataProvider $dataProvider, Location $location)
    {
        $providerUrl = $dataProvider->url;
        $lat = $location->lat;
        $lon = $location->lon;

        if ($dataProvider->request_method == 'GET') {
            if (!empty($dataProvider->payload)) {
                $payloadArray = json_decode($dataProvider->payload, true);
                $payloadArray = array_map(function($value) use ($lat, $lon) {
                    $value = str_replace(['{$lat}', '{$lon}'], [$lat, $lon], $value);
                    return $value;
                 }, $payloadArray);
                $response = Http::retry(3,100)
                    ->withQueryParameters($payloadArray)
                    ->get($providerUrl);
            } else {
                $response = Http::retry(3,100)->get($providerUrl);
            }
            if ($response->successful()) {
                if ($dataProvider->response_type == 'JSON') {
                    return $response->json();
                } else {
                    $this->jobLogMessage = 'Only JSON is currently supported as a response_type ' . $providerUrl;
                    Log::critical($this->jobLogMessage);
                    return false;
                }
            } else {
                $this->jobLogMessage = 'Could not fetch data from url ' . $providerUrl;
                Log::warning($this->jobLogMessage);
                return false;
            }
        } else {
            $this->jobLogMessage = 'Only GET is currently supported as a request_method.';
            Log::critical($this->jobLogMessage);
            return false;
        }
    }

    /**
     * Log how the job went
     * @param bool $status 0 for failure, 1 for success
     * @param int $dataProviderId data provider id
     * @param int $locationId location id
     * @param string $message log message
     */
    protected function logJob(bool $status, ?int $dataProviderId, ?int $locationId, string $message = '')
    {
        Log::channel('jobs_log')->info($message, [
            'location_id' => $locationId,
            'data_providers_id' => $dataProviderId,
            'success_status' => $status
        ]);
    }

    /**
     * @param array $normalizedData normalized data
     * @param int $dataProviderId data provider id
     * @param int $locationId location id
     * @return bool
     */
    protected function savePrecipitationDailyData(array $normalizedData, int $dataProviderId, int $locationId)
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

    /**
     * @param array $normalizedData normalized data
     * @param int $dataProviderId data provider id
     * @param int $locationId location id
     * @return bool
     */
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

    /**
     * @param array $normalizedData normalized data
     * @param int $dataProviderId data provider id
     * @param int $locationId location id
     * @return bool
     */
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

    /**
     * @param array $normalizedData normalized data
     * @param int $dataProviderId data provider id
     * @param int $locationId location id
     * @return bool
     */
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