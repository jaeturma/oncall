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
        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreignId('barangay_id')->nullable()->after('municipality_id')->constrained()->nullOnDelete();
            $table->decimal('latitude', 9, 6)->nullable()->after('barangay_id');
            $table->decimal('longitude', 9, 6)->nullable()->after('latitude');
            $table->string('address_line')->nullable()->after('longitude');
            $table->string('location_source')->nullable()->after('address_line');
            $table->timestamp('location_captured_at')->nullable()->after('location_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('barangay_id');
            $table->dropColumn(['latitude', 'longitude', 'address_line', 'location_source', 'location_captured_at']);
        });
    }
};
