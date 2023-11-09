<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('temperature_daily_forecasts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('location_id');
            $table->foreign('location_id')->references('id')->on('locations');
            $table->unsignedBigInteger('data_providers_id');
            $table->foreign('data_providers_id')->references('id')->on('data_providers');
            $table->date('forecast_day');
            $table->float('temperature_min');
            $table->float('temperature_max');
            $table->float('temperature_avg')->nullable();
            $table->float('apparent_temperature_min')->nullable();
            $table->float('apparent_temperature_max')->nullable();
            $table->float('apparent_temperature_avg')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temperature_daily_forecasts');
    }
};
