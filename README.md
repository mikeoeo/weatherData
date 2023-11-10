# weatherData
This project fascilitates the fetching and storing of precipitation and temperature data from any data provider for any location.

Regarding precipitation we gather the daily and hourly forecasted amount of rain in mm and the forecasted percentage of raining for the next 7 days and regarding temperature we gather the hourly forecasted temperature and temperature felt, along with the daily forecasted minimum, maximum and average temperature and apparent temperature in Celsius degrees for the next 7 days.

The project currently supports fetching data from [Open-Meteo](https://open-meteo.com/) and [WeatherAPI](https://www.weatherapi.com/), but more providers can be added in the future.
## Installation instructions
1. In order to run this project we should first change our directory to the root of the project, where our `docker-compose.yml` file can be found with
```
cd weatherData
```
2. Next we need to install our docker containers. Before this step, we need to make sure that we have docker installed. We can do that by running
```
docker -v
```
which will output the version of our docker engine. In case this does not happen, we need to install docker following [these instructions](https://docs.docker.com/engine/install/).

3. After we have the docker engine ready to go, we can now run
```
docker-compose up -d --build
```
This will create the necessary images and start our containers in the background.

**Important note**: This operation will also run composer install, so it may take around 6-8 minutes to finish.

4. After the laravel container is up and running, we have to connect to it to continue the next steps, so we should run
```
docker exec -it weatherdata-myapp-1 bash
```
where `weatherdata-myapp-1` is the name of our laravel container

5. From inside the container we are now ready to initialize our tables by running
```
php artisan migrate:fresh --seed
```

6. Our project is ready to fetch data! This can be done by running
```
php artisan app:fetch-forecast-data
```
which will try to fetch data and store data from all active providers for all locations.

7. This command has also been scheduled to run every day at midnight, all we need to do is run our cron installation script with
```
chmod +c cron.sh
./cron.sh
```
which will add the laravel scheduler in the container's crontab

## Adding more providers and locations
The project has been designed to support the addition of more providers and locations. 
### Adding providers
In order to add a provider we first need to add a new row in the `data_providers` table with 
* a `name`,
* the base `url` without any parameters,
* the desired `request_method` (only GET is currently supported),
* the `response_type` we expect to receive as a response (only JSON is currently supported), 
* the `payload`, which is json string that represents the parameters we want to pass with the url and
* the `active` column, which when set to 1 will put the provider in the active providers pool that is used to fetch data from.

Additionally, we need to create a normalizer function in the `laravel\app\Services\NormalizerService.php` file that will normalize the data we receive from the API call in a predefined format so that they are ready to be stored in the database.

We also need to create a unit test function in the `laravel\tests\Unit\NormalizeProviderTest.php` file that will verify the correct functionality of the new normalizer function. For the test we just need to paste the response we get from the API call as a string variable, convert it to an associative array, pass it through our newly created normalize function and then run the tests by feeding the result to the `isNormalizedArray` function.

### Adding locations
In order to add more locations we just need to add them as new rows to the `locations` table with 
* a `name`,
* a `lat` (latitdue) value and
* a `lon` (longitude) value. 

## Future development
Regarding future development, the addition of more providers may create additional needs, like for instance the support for POST calls or support for CSV and/or XML responses. Some providers may also require additional headers in each call.

Furthermore, depending on the pricing plan of some providers, we may need to implement some type of load balancing in case we add too many locations.
