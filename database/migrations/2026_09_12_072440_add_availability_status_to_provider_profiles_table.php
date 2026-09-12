<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds a four-state availability status alongside the legacy boolean.
     * `available_now` is kept in sync by the model so existing search ordering
     * and filters keep working unchanged.
     */
    public function up(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->string('availability_status')->default('OFFLINE')->after('available_now')->index();
        });

        DB::table('provider_profiles')->where('available_now', true)->update(['availability_status' => 'AVAILABLE']);
    }

    public function down(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->dropIndex(['availability_status']);
            $table->dropColumn('availability_status');
        });
    }
};
