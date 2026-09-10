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
        Schema::create('provider_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('province_id')->constrained()->restrictOnDelete();
            $table->foreignId('municipality_id')->constrained()->restrictOnDelete();
            $table->text('bio')->nullable();
            $table->boolean('available_now')->default(false);
            $table->unsignedSmallInteger('service_radius_km')->nullable();
            $table->string('verification_status')->default('PENDING')->index();
            $table->json('credentials_metadata')->nullable();
            $table->decimal('rating_cached', 3, 2)->nullable();
            $table->unsignedInteger('completed_jobs_cached')->default(0);
            $table->timestamps();
            $table->index(['province_id', 'municipality_id', 'available_now'], 'provider_location_availability_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_profiles');
    }
};
