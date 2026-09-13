<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per configured SMS provider (Phase L). `config` holds every
     * driver-specific field (base URL, auth type, credentials, sender id,
     * ...) as a single encrypted JSON blob — `SmsProvider::$casts` encrypts
     * it at rest, so no individual "api_key"/"api_secret" columns are
     * needed, and adding a new provider driver never requires a migration.
     */
    public function up(): void
    {
        Schema::create('sms_providers', function (Blueprint $table) {
            $table->id();
            $table->string('driver');
            $table->string('name');
            $table->text('config')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_providers');
    }
};
