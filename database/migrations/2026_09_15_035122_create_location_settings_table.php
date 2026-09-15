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
        Schema::create('location_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('maps_enabled')->default(true);
            $table->string('active_map_provider')->default('OPENSTREETMAP');
            $table->boolean('geocoding_enabled')->default(false);
            $table->string('geocoding_base_url')->nullable();
            $table->unsignedSmallInteger('default_search_radius_km')->default(10);
            $table->unsignedSmallInteger('max_search_radius_km')->default(50);
            $table->json('allowed_radius_choices');
            $table->string('default_country', 2)->default('PH');
            $table->unsignedSmallInteger('location_freshness_days')->default(90);
            $table->string('provider_location_policy')->default('OPTIONAL');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_settings');
    }
};
