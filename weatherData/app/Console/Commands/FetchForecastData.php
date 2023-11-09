<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NormalizerService;
use App\Services\WeatherForecastService;

class FetchForecastData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-forecast-data {dataProviderId=0} {locationId=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch temperature and precipitation forecast data from providers for locations';

    /**
     * Execute the console command.
     */
    public function handle(WeatherForecastService $weatherForecastService)
    {
        $dataProviderId = $this->argument('dataProviderId');
        $locationId = $this->argument('locationId');
        $weatherForecastService->fetchAllData($dataProviderId, $locationId);
    }
}
