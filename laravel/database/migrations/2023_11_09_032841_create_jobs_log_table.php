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
        Schema::create('jobs_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->foreign('location_id')->references('id')->on('locations');
            $table->unsignedBigInteger('data_providers_id')->nullable();
            $table->foreign('data_providers_id')->references('id')->on('data_providers');
            $table->boolean('success_status');
            $table->longText('message');
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs_log');
    }
};
