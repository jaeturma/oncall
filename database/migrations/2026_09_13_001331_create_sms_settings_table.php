<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Singleton settings row (Phase L) — `SmsSetting::current()` always
     * reads/creates id 1. Holds the global SMS on/off switch, which
     * provider is active, and every admin-configurable OTP policy bound
     * (Step 22 of the phase spec), so `OtpService` never hard-codes them.
     */
    public function up(): void
    {
        Schema::create('sms_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->foreignId('active_sms_provider_id')->nullable()->constrained('sms_providers')->nullOnDelete();
            $table->unsignedTinyInteger('otp_length')->default(6);
            $table->unsignedTinyInteger('otp_expiry_minutes')->default(5);
            $table->unsignedSmallInteger('otp_resend_cooldown_seconds')->default(60);
            $table->unsignedTinyInteger('otp_max_attempts')->default(5);
            $table->unsignedTinyInteger('otp_max_sends_per_mobile_per_hour')->default(5);
            $table->unsignedTinyInteger('otp_max_sends_per_ip_per_hour')->default(20);
            $table->string('test_mobile_number')->nullable();
            // No DB-level default: MySQL/MariaDB reject a literal default on
            // TEXT columns. SmsSetting::$attributes supplies it instead.
            $table->text('otp_message_template');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_settings');
    }
};
