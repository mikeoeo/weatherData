<?php

namespace Tests\Unit;

use App\Services\NormalizerService;
use PHPUnit\Framework\TestCase;

class NormalizeProviderTest extends TestCase
{
    const EXPECTED_NORMALIZED_RESULT = [
        'precipitation' => [
            'daily' => [
                0 => [
                'forecast_day' => '',
                'amount' => 0,
                'percentage' => 0,
                ],
            ],
            'hourly' => [
                0 => [
                'forecast_datetime' => '',
                'amount' => 0,
                'percentage' => 0,
                ],
                // ...
            ],
        ],
        'temperature' => [
            'daily' => [
                0 => [
                'forecast_day' => '',
                'temperature_min' => 0,
                'temperature_max' => 0,
                'temperature_avg' => 0,
                'apparent_temperature_min' => 0,
                'apparent_temperature_max' => 0,
                'apparent_temperature_avg' => 0,
                ],
            ],
            'hourly' => [
                0 => [
                'forecast_datetime' => '',
                'temperature' => 0,
                'temperature_felt' => 0,
                ],
                // ...
            ],
        ],
    ];

    /**
     * normalizeOpenMeteo unit test
     */
    public function test_normalizeOpenMeteo(): void
    {
        $jsonFromOpenMeteo = '{"latitude":40.5625,"longitude":23.0,"generationtime_ms":0.12004375457763672,"utc_offset_seconds":10800,"timezone":"Europe/Moscow","timezone_abbreviation":"MSK","elevation":11.0,"hourly_units":{"time":"iso8601","temperature_2m":"°C","apparent_temperature":"°C","precipitation_probability":"%","rain":"mm"},"hourly":{"time":["2023-11-10T00:00","2023-11-10T01:00","2023-11-10T02:00","2023-11-10T03:00","2023-11-10T04:00","2023-11-10T05:00","2023-11-10T06:00","2023-11-10T07:00","2023-11-10T08:00","2023-11-10T09:00","2023-11-10T10:00","2023-11-10T11:00","2023-11-10T12:00","2023-11-10T13:00","2023-11-10T14:00","2023-11-10T15:00","2023-11-10T16:00","2023-11-10T17:00","2023-11-10T18:00","2023-11-10T19:00","2023-11-10T20:00","2023-11-10T21:00","2023-11-10T22:00","2023-11-10T23:00"],"temperature_2m":[13.4,13.2,12.9,13.9,13.8,13.7,13.1,12.9,12.5,12.9,13.9,14.7,15.5,16.1,16.4,16.5,16.4,16.4,16.0,15.7,15.7,15.6,15.5,15.4],"apparent_temperature":[12.7,12.3,12.0,12.8,12.8,12.6,12.4,12.2,11.6,11.9,13.4,13.8,13.9,14.3,14.0,14.2,14.5,14.6,14.2,14.3,14.4,14.7,15.0,15.3],"precipitation_probability":[13,9,4,0,0,0,0,0,0,0,0,0,0,5,11,16,29,42,55,53,50,48,48,48],"rain":[0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.20,0.00,0.00,0.00,0.00,0.10,0.00,0.00]},"daily_units":{"time":"iso8601","temperature_2m_max":"°C","temperature_2m_min":"°C","apparent_temperature_max":"°C","apparent_temperature_min":"°C","precipitation_sum":"mm","precipitation_probability_max":"%"},"daily":{"time":["2023-11-10"],"temperature_2m_max":[16.5],"temperature_2m_min":[12.5],"apparent_temperature_max":[15.3],"apparent_temperature_min":[11.6],"precipitation_sum":[0.40],"precipitation_probability_max":[55]}}';

        $normalizerService = new NormalizerService();
        $result = $normalizerService->normalize('OpenMeteo', json_decode($jsonFromOpenMeteo, true));

        $this->assertIsArray($result);
        $this->isNormalizedArray($result);
    }

    /**
     * normalizeWeatherApi unit test
     */
    public function test_normalizeWeatherApi(): void
    {
        $jsonFromWeatherApi = '{"location":{"name":"Thessaloniki","region":"Central Macedonia","country":"Greece","lat":40.64,"lon":22.93,"tz_id":"Europe/Athens","localtime_epoch":1699608342,"localtime":"2023-11-10 11:25"},"current":{"last_updated_epoch":1699607700,"last_updated":"2023-11-10 11:15","temp_c":16.0,"temp_f":60.8,"is_day":1,"condition":{"text":"Partly cloudy","icon":"//cdn.weatherapi.com/weather/64x64/day/116.png","code":1003},"wind_mph":2.2,"wind_kph":3.6,"wind_degree":10,"wind_dir":"N","pressure_mb":1019.0,"pressure_in":30.09,"precip_mm":0.01,"precip_in":0.0,"humidity":63,"cloud":75,"feelslike_c":16.0,"feelslike_f":60.8,"vis_km":10.0,"vis_miles":6.0,"uv":3.0,"gust_mph":9.1,"gust_kph":14.7},"forecast":{"forecastday":[{"date":"2023-11-10","date_epoch":1699574400,"day":{"maxtemp_c":17.0,"maxtemp_f":62.6,"mintemp_c":12.8,"mintemp_f":55.1,"avgtemp_c":14.7,"avgtemp_f":58.4,"maxwind_mph":12.1,"maxwind_kph":19.4,"totalprecip_mm":0.32,"totalprecip_in":0.01,"totalsnow_cm":0.0,"avgvis_km":10.0,"avgvis_miles":6.0,"avghumidity":66.0,"daily_will_it_rain":1,"daily_chance_of_rain":86,"daily_will_it_snow":0,"daily_chance_of_snow":0,"condition":{"text":"Patchy rain possible","icon":"//cdn.weatherapi.com/weather/64x64/day/176.png","code":1063},"uv":3.0},"astro":{"sunrise":"07:09 AM","sunset":"05:16 PM","moonrise":"03:56 AM","moonset":"03:49 PM","moon_phase":"Waning Crescent","moon_illumination":11,"is_moon_up":0,"is_sun_up":0},"hour":[{"time_epoch":1699567200,"time":"2023-11-10 00:00","temp_c":14.1,"temp_f":57.4,"is_day":0,"condition":{"text":"Patchy rain possible","icon":"//cdn.weatherapi.com/weather/64x64/night/176.png","code":1063},"wind_mph":4.3,"wind_kph":6.8,"wind_degree":135,"wind_dir":"SE","pressure_mb":1021.0,"pressure_in":30.15,"precip_mm":0.04,"precip_in":0.0,"humidity":68,"cloud":75,"feelslike_c":14.0,"feelslike_f":57.3,"windchill_c":14.0,"windchill_f":57.3,"heatindex_c":14.1,"heatindex_f":57.4,"dewpoint_c":8.4,"dewpoint_f":47.1,"will_it_rain":0,"chance_of_rain":68,"will_it_snow":0,"chance_of_snow":0,"vis_km":10.0,"vis_miles":6.0,"gust_mph":7.0,"gust_kph":11.3,"uv":1.0},{"time_epoch":1699570800,"time":"2023-11-10 01:00","temp_c":14.0,"temp_f":57.2,"is_day":0,"condition":{"text":"Patchy rain possible","icon":"//cdn.weatherapi.com/weather/64x64/night/176.png","code":1063},"wind_mph":3.6,"wind_kph":5.8,"wind_degree":119,"wind_dir":"ESE","pressure_mb":1021.0,"pressure_in":30.14,"precip_mm":0.02,"precip_in":0.0,"humidity":67,"cloud":80,"feelslike_c":14.1,"feelslike_f":57.4,"windchill_c":14.1,"windchill_f":57.4,"heatindex_c":14.0,"heatindex_f":57.2,"dewpoint_c":7.9,"dewpoint_f":46.2,"will_it_rain":1,"chance_of_rain":86,"will_it_snow":0,"chance_of_snow":0,"vis_km":10.0,"vis_miles":6.0,"gust_mph":5.9,"gust_kph":9.4,"uv":1.0},{"time_epoch":1699574400,"time":"2023-11-10 02:00","temp_c":13.7,"temp_f":56.7,"is_day":0,"condition":{"text":"Patchy rain possible","icon":"//cdn.weatherapi.com/weather/64x64/night/176.png","code":1063},"wind_mph":3.6,"wind_kph":5.8,"wind_degree":118,"wind_dir":"ESE","pressure_mb":1020.0,"pressure_in":30.13,"precip_mm":0.01,"precip_in":0.0,"humidity":66,"cloud":77,"feelslike_c":13.8,"feelslike_f":56.8,"windchill_c":13.8,"windchill_f":56.8,"heatindex_c":13.7,"heatindex_f":56.7,"dewpoint_c":7.6,"dewpoint_f":45.7,"will_it_rain":1,"chance_of_rain":83,"will_it_snow":0,"chance_of_snow":0,"vis_km":10.0,"vis_miles":6.0,"gust_mph":6.0,"gust_kph":9.6,"uv":1.0},{"time_epoch":1699578000,"time":"2023-11-10 03:00","temp_c":13.5,"temp_f":56.3,"is_day":0,"condition":{"text":"Patchy rain possible","icon":"//cdn.weatherapi.com/weather/64x64/night/176.png","code":1063},"wind_mph":3.1,"wind_kph":5.0,"wind_degree":106,"wind_dir":"ESE","pressure_mb":1020.0,"pressure_in":30.12,"precip_mm":0.01,"precip_in":0.0,"humidity":66,"cloud":77,"feelslike_c":13.7,"feelslike_f":56.7,"windchill_c":13.7,"windchill_f":56.7,"heatindex_c":13.5,"heatindex_f":56.3,"dewpoint_c":7.4,"dewpoint_f":45.3,"will_it_rain":0,"chance_of_rain":67,"will_it_snow":0,"chance_of_snow":0,"vis_km":10.0,"vis_miles":6.0,"gust_mph":5.2,"gust_kph":8.4,"uv":1.0},{"time_epoch":1699581600,"time":"2023-11-10 04:00","temp_c":13.2,"temp_f":55.8,"is_day":0,"condition":{"text":"Partly cloudy","icon":"//cdn.weatherapi.com/weather/64x64/night/116.png","code":1003},"wind_mph":4.0,"wind_kph":6.5,"wind_degree":104,"wind_dir":"ESE","pressure_mb":1020.0,"pressure_in":30.11,"precip_mm":0.0,"precip_in":0.0,"humidity":66,"cloud":48,"feelslike_c":13.1,"feelslike_f":55.5,"windchill_c":13.1,"windchill_f":55.5,"heatindex_c":13.2,"heatindex_f":55.8,"dewpoint_c":7.1,"dewpoint_f":44.8,"will_it_rain":0,"chance_of_rain":0,"will_it_snow":0,"chance_of_snow":0,"vis_km":10.0,"vis_miles":6.0,"gust_mph":6.8,"gust_kph":10.9,"uv":1.0},{"time_epoch":1699585200,"time":"2023-11-10 05:00","temp_c":13.1,"temp_f":55.6,"is_day":0,"condition":{"text":"Cloudy","icon":"//cdn.weatherapi.com/weather/64x64/night/119.png","code":1006},"wind_mph":4.9,"wind_kph":7.9,"wind_degree":98,"wind_dir":"E","pressure_mb":1020.0,"pressure_in":30.11,"precip_mm":0.0,"precip_in":0.0,"humidity":65,"cloud":73,"feelslike_c":12.7,"feelslike_f":54.8,"windchill_c":12.7,"windchill_f":54.8,"heatindex_c":13.1,"heatindex_f":55.6,"dewpoint_c":6.8,"dewpoint_f":44.2,"will_it_rain":0,"chance_of_rain":0,"will_it_snow":0,"chance_of_snow":0,"vis_km":10.0,"vis_miles":6.0,"gust_mph":8.2,"gust_kph":13.2,"uv":1.0},{"time_epoch":1699588800,"time":"2023-11-10 06:00","temp_c":13.2,"temp_f":55.8,"is_day":0,"condition":{"text":"Cloudy","icon":"//cdn.weatherapi.com/weather/64x64/night/119.png","code":1006},"wind_mph":6.0,"wind_kph":9.7,"wind_degree":92,"wind_dir":"E","pressure_mb":1019.0,"pressure_in":30.09,"precip_mm":0.0,"precip_in":0.0,"humidity":64,"cloud":64,"feelslike_c":12.6,"feelslike_f":54.6,"windchill_c":12.6,"windchill_f":54.6,"heatindex_c":13.2,"heatindex_f":55.8,"dewpoint_c":6.5,"dewpoint_f":43.7,"will_it_rain":0,"chance_of_rain":0,"will_it_snow":0,"chance_of_snow":0,"vis_km":10.0,"vis_miles":6.0,"gust_mph":9.6,"gust_kph":15.5,"uv":1.0},{"time_epoch":1699592400,"time":"2023-11-10 07:00","temp_c":12.8,"temp_f":55.1,"is_day":0,"condition":{"text":"Patchy rain possible","icon":"//cdn.weatherapi.com/weather/64x64/night/176.png","code":1063},"wind_mph":4.9,"wind_kph":7.9,"wind_degree":102,"wind_dir":"ESE","pressure_mb":1019.0,"pressure_in":30.1,"precip_mm":0.01,"precip_in":0.0,"humidity":65,"cloud":97,"feelslike_c":12.3,"feelslike_f":54.2,"windchill_c":12.3,"windchill_f":54.2,"heatindex_c":12.8,"heatindex_f":55.1,"dewpoint_c":6.4,"dewpoint_f":43.6,"will_it_rain":0,"chance_of_rain":61,"will_it_snow":0,"chance_of_snow":0,"vis_km":10.0,"vis_miles":6.0,"gust_mph":8.1,"gust_kph":13.0,"uv":1.0},{"time_epoch":1699596000,"time":"2023-11-10 08:00","temp_c":13.3,"temp_f":55.9,"is_day":1,"condition":{"text":"Patchy rain possible","icon":"//cdn.weatherapi.com/weather/64x64/day/176.png","code":1063},"wind_mph":4.9,"wind_kph":7.9,"wind_degree":101,"wind_dir":"ESE","pressure_mb":1019.0,"pressure_in":30.1,"precip_mm":0.01,"precip_in":0.0,"humidity":64,"cloud":100,"feelslike_c":12.9,"feelslike_f":55.2,"windchill_c":12.9,"windchill_f":55.2,"heatindex_c":13.3,"heatindex_f":55.9,"dewpoint_c":6.6,"dewpoint_f":44.0,"will_it_rain":1,"chance_of_rain":71,"will_it_snow":0,"chance_of_snow":0,"vis_km":10.0,"vis_miles":6.0,"gust_mph":7.4,"gust_kph":11.9,"uv":3.0},{"time_epoch":1699599600,"time":"2023-11-10 09:00","temp_c":13.7,"temp_f":56.7,"is_day":1,"condition":{"text":"Overcast","icon":"//cdn.weatherapi.com/weather/64x64/day/122.png","code":1009},"wind_mph":5.8,"wind_kph":9.4,"wind_degree":106,"wind_dir":"ESE","pressure_mb":1019.0,"pressure_in":30.1,"precip_mm":0.0,"precip_in":0.0,"humidity":63,"cloud":100,"feelslike_c":13.2,"feelslike_f":55.7,"windchill_c":13.2,"windchill_f":55.7,"heatindex_c":13.7,"heatindex_f":56.7,"dewpoint_c":6.9,"dewpoint_f":44.5,"will_it_rain":0,"chance_of_rain":0,"will_it_snow":0,"chance_of_snow":0,"vis_km":10.0,"vis_miles":6.0,"gust_mph":8.3,"gust_kph":13.3,"uv":3.0},{"time_epoch":1699603200,"time":"2023-11-10 10:00","temp_c":14.3,"temp_f":57.7,"is_day":1,"condition":{"text":"Patchy rain possible","icon":"//cdn.weatherapi.com/weather/64x64/day/176.png","code":1063},"wind_mph":6.3,"wind_kph":10.1,"wind_degree":114,"wind_dir":"ESE","pressure_mb":1019.0,"pressure_in":30.1,"precip_mm":0.01,"precip_in":0.0,"humidity":63,"cloud":100,"feelslike_c":13.8,"feelslike_f":56.8,"windchill_c":13.8,"windchill_f":56.8,"heatindex_c":14.3,"heatindex_f":57.7,"dewpoint_c":7.4,"dewpoint_f":45.3,"will_it_rain":0,"chance_of_rain":69,"will_it_snow":0,"chance_of_snow":0,"vis_km":10.0,"vis_miles":6.0,"gust_mph":8.4,"gust_kph":13.5,"uv":3.0},{"time_epoch":1699606800,"time":"2023-11-10 11:00","temp_c":16.0,"temp_f":60.8,"is_day":1,"condition":{"text":"Partly cloudy","icon":"//cdn.weatherapi.com/weather/64x64/day/116.png","code":1003},"wind_mph":2.2,"wind_kph":3.6,"wind_degree":10,"wind_dir":"N","pressure_mb":1019.0,"pressure_in":30.09,"precip_mm":0.01,"precip_in":0.0,"humidity":63,"cloud":75,"feelslike_c":14.3,"feelslike_f":57.7,"windchill_c":14.3,"windchill_f":57.7,"heatindex_c":14.8,"heatindex_f":58.7,"dewpoint_c":7.7,"dewpoint_f":45.9,"will_it_rain":0,"chance_of_rain":68,"will_it_snow":0,"chance_of_snow":0,"vis_km":10.0,"vis_miles":6.0,"gust_mph":9.1,"gust_kph":14.7,"uv":3.0},{"time_epoch":1699610400,"time":"2023-11-10 12:00","temp_c":15.5,"temp_f":60.0,"is_day":1,"condition":{"text":"Patchy rain possible","icon":"//cdn.weatherapi.com/weather/64x64/day/176.png","code":1063},"wind_mph":6.9,"wind_kph":11.2,"wind_degree":138,"wind_dir":"SE","pressure_mb":1018.0,"pressure_in":30.06,"precip_mm":0.01,"precip_in":0.0,"humidity":60,"cloud":100,"feelslike_c":15.5,"feelslike_f":60.0,"windchill_c":15.5,"windchill_f":60.0,"heatindex_c":15.5,"heatindex_f":60.0,"dewpoint_c":7.8,"dewpoint_f":46.1,"will_it_rain":0,"chance_of_rain":69,"will_it_snow":0,"chance_of_snow":0,"vis_km":10.0,"vis_miles":6.0,"gust_mph":9.0,"gust_kph":14.5,"uv":4.0},{"time_epoch":1699614000,"time":"2023-11-10 13:00","temp_c":16.1,"temp_f":61.0,"is_day":1,"condition":{"text":"Patchy rain possible","icon":"//cdn.weatherapi.com/weather/64x64/day/176.png","code":1063},"wind_mph":7.6,"wind_kph":12.2,"wind_degree":152,"wind_dir":"SSE","pressure_mb":1017.0,"pressure_in":30.03,"precip_mm":0.01,"precip_in":0.0,"humidity":59,"cloud":92,"feelslike_c":16.1,"feelslike_f":61.1,"windchill_c":16.1,"windchill_f":61.1,"heatindex_c":16.1,"heatindex_f":61.1,"dewpoint_c":8.1,"dewpoint_f":46.6,"will_it_rain":1,"chance_of_rain":72,"will_it_snow":0,"chance_of_snow":0,"vis_km":10.0,"vis_miles":6.0,"gust_mph":9.8,"gust_kph":15.8,"uv":4.0},{"time_epoch":1699617600,"time":"2023-11-10 14:00","temp_c":16.7,"temp_f":62.1,"is_day":1,"condition":{"text":"Patchy rain possible","icon":"//cdn.weatherapi.com/weather/64x64/day/176.png","code":1063},"wind_mph":9.2,"wind_kph":14.8,"wind_degree":156,"wind_dir":"SSE","pressure_mb":1016.0,"pressure_in":30.0,"precip_mm":0.01,"precip_in":0.0,"humidity":58,"cloud":62,"feelslike_c":16.7,"feelslike_f":62.1,"windchill_c":16.7,"windchill_f":62.1,"heatindex_c":16.7,"heatindex_f":62.1,"dewpoint_c":8.4,"dewpoint_f":47.2,"will_it_rain":0,"chance_of_rain":66,"will_it_snow":0,"chance_of_snow":0,"vis_km":10.0,"vis_miles":6.0,"gust_mph":11.7,"gust_kph":18.8,"uv":4.0},{"time_epoch":1699621200,"time":"2023-11-10 15:00","temp_c":17.0,"temp_f":62.6,"is_day":1,"condition":{"text":"Overcast","icon":"//cdn.weatherapi.com/weather/64x64/day/122.png","code":1009},"wind_mph":11.2,"wind_kph":18.0,"wind_degree":158,"wind_dir":"SSE","pressure_mb":1015.0,"pressure_in":29.98,"precip_mm":0.0,"precip_in":0.0,"humidity":58,"cloud":100,"feelslike_c":17.0,"feelslike_f":62.6,"windchill_c":17.0,"windchill_f":62.6,"heatindex_c":17.0,"heatindex_f":62.6,"dewpoint_c":8.6,"dewpoint_f":47.5,"will_it_rain":0,"chance_of_rain":0,"will_it_snow":0,"chance_of_snow":0,"vis_km":10.0,"vis_miles":6.0,"gust_mph":14.2,"gust_kph":22.9,"uv":4.0},{"time_epoch":1699624800,"time":"2023-11-10 16:00","temp_c":16.4,"temp_f":61.5,"is_day":1,"condition":{"text":"Partly cloudy","icon":"//cdn.weatherapi.com/weather/64x64/day/116.png","code":1003},"wind_mph":9.6,"wind_kph":15.5,"wind_degree":145,"wind_dir":"SE","pressure_mb":1014.0,"pressure_in":29.95,"precip_mm":0.0,"precip_in":0.0,"humidity":61,"cloud":56,"feelslike_c":16.4,"feelslike_f":61.5,"windchill_c":16.4,"windchill_f":61.5,"heatindex_c":16.4,"heatindex_f":61.5,"dewpoint_c":8.9,"dewpoint_f":48.0,"will_it_rain":0,"chance_of_rain":0,"will_it_snow":0,"chance_of_snow":0,"vis_km":10.0,"vis_miles":6.0,"gust_mph":13.1,"gust_kph":21.1,"uv":5.0},{"time_epoch":1699628400,"time":"2023-11-10 17:00","temp_c":15.8,"temp_f":60.5,"is_day":1,"condition":{"text":"Patchy rain possible","icon":"//cdn.weatherapi.com/weather/64x64/day/176.png","code":1063},"wind_mph":12.1,"wind_kph":19.4,"wind_degree":152,"wind_dir":"SSE","pressure_mb":1015.0,"pressure_in":29.96,"precip_mm":0.01,"precip_in":0.0,"humidity":65,"cloud":69,"feelslike_c":15.8,"feelslike_f":60.5,"windchill_c":15.8,"windchill_f":60.5,"heatindex_c":15.8,"heatindex_f":60.5,"dewpoint_c":9.2,"dewpoint_f":48.5,"will_it_rain":0,"chance_of_rain":66,"will_it_snow":0,"chance_of_snow":0,"vis_km":10.0,"vis_miles":6.0,"gust_mph":17.5,"gust_kph":28.2,"uv":4.0},{"time_epoch":1699632000,"time":"2023-11-10 18:00","temp_c":15.7,"temp_f":60.2,"is_day":0,"condition":{"text":"Patchy rain possible","icon":"//cdn.weatherapi.com/weather/64x64/night/176.png","code":1063},"wind_mph":11.6,"wind_kph":18.7,"wind_degree":134,"wind_dir":"SE","pressure_mb":1015.0,"pressure_in":29.96,"precip_mm":0.01,"precip_in":0.0,"humidity":67,"cloud":72,"feelslike_c":15.7,"feelslike_f":60.2,"windchill_c":15.7,"windchill_f":60.2,"heatindex_c":15.7,"heatindex_f":60.2,"dewpoint_c":9.6,"dewpoint_f":49.2,"will_it_rain":0,"chance_of_rain":69,"will_it_snow":0,"chance_of_snow":0,"vis_km":10.0,"vis_miles":6.0,"gust_mph":16.6,"gust_kph":26.8,"uv":1.0},{"time_epoch":1699635600,"time":"2023-11-10 19:00","temp_c":15.2,"temp_f":59.4,"is_day":0,"condition":{"text":"Patchy rain possible","icon":"//cdn.weatherapi.com/weather/64x64/night/176.png","code":1063},"wind_mph":8.9,"wind_kph":14.4,"wind_degree":122,"wind_dir":"ESE","pressure_mb":1014.0,"pressure_in":29.95,"precip_mm":0.03,"precip_in":0.0,"humidity":71,"cloud":84,"feelslike_c":15.2,"feelslike_f":59.4,"windchill_c":15.2,"windchill_f":59.4,"heatindex_c":15.2,"heatindex_f":59.4,"dewpoint_c":10.0,"dewpoint_f":50.0,"will_it_rain":1,"chance_of_rain":84,"will_it_snow":0,"chance_of_snow":0,"vis_km":10.0,"vis_miles":6.0,"gust_mph":12.9,"gust_kph":20.8,"uv":1.0},{"time_epoch":1699639200,"time":"2023-11-10 20:00","temp_c":15.0,"temp_f":59.1,"is_day":0,"condition":{"text":"Patchy rain possible","icon":"//cdn.weatherapi.com/weather/64x64/night/176.png","code":1063},"wind_mph":9.8,"wind_kph":15.8,"wind_degree":127,"wind_dir":"SE","pressure_mb":1014.0,"pressure_in":29.95,"precip_mm":0.08,"precip_in":0.0,"humidity":73,"cloud":100,"feelslike_c":15.0,"feelslike_f":59.1,"windchill_c":15.0,"windchill_f":59.1,"heatindex_c":15.0,"heatindex_f":59.1,"dewpoint_c":10.3,"dewpoint_f":50.6,"will_it_rain":0,"chance_of_rain":62,"will_it_snow":0,"chance_of_snow":0,"vis_km":10.0,"vis_miles":6.0,"gust_mph":14.4,"gust_kph":23.2,"uv":1.0},{"time_epoch":1699642800,"time":"2023-11-10 21:00","temp_c":15.2,"temp_f":59.4,"is_day":0,"condition":{"text":"Patchy rain possible","icon":"//cdn.weatherapi.com/weather/64x64/night/176.png","code":1063},"wind_mph":9.2,"wind_kph":14.8,"wind_degree":125,"wind_dir":"SE","pressure_mb":1014.0,"pressure_in":29.94,"precip_mm":0.04,"precip_in":0.0,"humidity":74,"cloud":74,"feelslike_c":15.2,"feelslike_f":59.4,"windchill_c":15.2,"windchill_f":59.4,"heatindex_c":15.2,"heatindex_f":59.4,"dewpoint_c":10.6,"dewpoint_f":51.2,"will_it_rain":1,"chance_of_rain":78,"will_it_snow":0,"chance_of_snow":0,"vis_km":10.0,"vis_miles":6.0,"gust_mph":12.9,"gust_kph":20.8,"uv":1.0},{"time_epoch":1699646400,"time":"2023-11-10 22:00","temp_c":14.9,"temp_f":58.9,"is_day":0,"condition":{"text":"Patchy rain possible","icon":"//cdn.weatherapi.com/weather/64x64/night/176.png","code":1063},"wind_mph":7.6,"wind_kph":12.2,"wind_degree":118,"wind_dir":"ESE","pressure_mb":1014.0,"pressure_in":29.93,"precip_mm":0.01,"precip_in":0.0,"humidity":76,"cloud":71,"feelslike_c":14.3,"feelslike_f":57.7,"windchill_c":14.3,"windchill_f":57.7,"heatindex_c":14.9,"heatindex_f":58.9,"dewpoint_c":10.8,"dewpoint_f":51.5,"will_it_rain":0,"chance_of_rain":63,"will_it_snow":0,"chance_of_snow":0,"vis_km":10.0,"vis_miles":6.0,"gust_mph":11.1,"gust_kph":17.9,"uv":1.0},{"time_epoch":1699650000,"time":"2023-11-10 23:00","temp_c":14.4,"temp_f":57.9,"is_day":0,"condition":{"text":"Patchy rain possible","icon":"//cdn.weatherapi.com/weather/64x64/night/176.png","code":1063},"wind_mph":7.6,"wind_kph":12.2,"wind_degree":107,"wind_dir":"ESE","pressure_mb":1013.0,"pressure_in":29.91,"precip_mm":0.01,"precip_in":0.0,"humidity":79,"cloud":75,"feelslike_c":13.6,"feelslike_f":56.5,"windchill_c":13.6,"windchill_f":56.5,"heatindex_c":14.4,"heatindex_f":57.9,"dewpoint_c":10.8,"dewpoint_f":51.4,"will_it_rain":1,"chance_of_rain":79,"will_it_snow":0,"chance_of_snow":0,"vis_km":10.0,"vis_miles":6.0,"gust_mph":11.8,"gust_kph":19.0,"uv":1.0}]}]}}';

        $normalizerService = new NormalizerService();
        $result = $normalizerService->normalize('WeatherApi', json_decode($jsonFromWeatherApi, true));

        $this->assertIsArray($result);
        $this->isNormalizedArray($result);
    }

    private function isNormalizedArray($result)
    {
        foreach (['precipitation', 'temperature'] as $dataType)
        {
            $this->assertArrayHasKey($dataType, $result);
            $this->assertIsArray($result[$dataType]);
            
            foreach (['daily', 'hourly'] as $timeliness)
            {
                $this->assertArrayHasKey($timeliness, $result[$dataType]);
                $this->assertIsArray($result[$dataType][$timeliness]);
                $this->assertIsArray($result[$dataType][$timeliness][0]);
                $this->assertEquals(array_keys(self::EXPECTED_NORMALIZED_RESULT[$dataType][$timeliness][0]), array_keys($result[$dataType][$timeliness][0]));
            }
        }
    }
}
