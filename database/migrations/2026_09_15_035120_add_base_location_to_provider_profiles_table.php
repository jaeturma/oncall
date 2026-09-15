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
        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->foreignId('barangay_id')->nullable()->after('municipality_id')->constrained()->restrictOnDelete();
            $table->decimal('latitude', 9, 6)->nullable()->after('barangay_id');
            $table->decimal('longitude', 9, 6)->nullable()->after('latitude');
            $table->string('location_source')->nullable()->after('longitude');
            $table->timestamp('location_updated_at')->nullable()->after('location_source');
            $table->index(['latitude', 'longitude'], 'provider_profiles_coordinates_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->dropIndex('provider_profiles_coordinates_idx');
            $table->dropConstrainedForeignId('barangay_id');
            $table->dropColumn(['latitude', 'longitude', 'location_source', 'location_updated_at']);
        });
    }
};
