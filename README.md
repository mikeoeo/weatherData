# weatherData
This project fascilitates the fetching and storing of weather data from any data provider for any location
## Installation instructions
1. In order to run this project we should first change our directory to the root of the project, where our `docker-compose.yml` file can be found.
2. Next we need to install our docker containers. Before this step, we need to make sure that we have docker installed. We can do that by running
```
docker -v
```
which will output the version of our docker engine. In case this does not happen, we need to install docker following [these instructions](https://docs.docker.com/engine/install/).

3. After we have the docker engine ready to go, we can now run
```
docker-compose up -d --build
```
This will create the necessary images and start our two containers in the background.

4. The next step is the
```
composer install
```
which will fetch and install all the necessary libraries and tools that our project requires to run ([installation instructions if needed](https://getcomposer.org/download/))

5. This is a good time to create a `.env` file in the `laravel` folder that has our main laravel code so that we can connect with our database. The default values for the DB variables are
```
DB_DATABASE=weather_db
DB_USERNAME=weather_user
DB_PASSWORD=pass
```

6. We are now ready to initialize our tables by running
```
php artisan migrate:fresh --seed
```

7. Our project is ready to fetch data! This can be done by running
```
php artisan app:fetch-forecast-data
```

8. This command has also been scheduled to run every day at midnight, all we need to do is add the artisan scheduler run command entry to our crontab
```
* * * * * cd /path-to-the-project && php artisan schedule:run >> /dev/null 2>&1
```
In case we want to schedule this task on Windows, we can find the appropriate instructions [here](https://gist.github.com/Splode/94bfa9071625e38f7fd76ae210520d94)
